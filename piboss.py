#!/usr/bin/python
# encoding: utf-8
from __future__ import division
import socket
import busio
import board
import RPi.GPIO as GPIO
from datetime import datetime
from datetime import timedelta
from time import sleep
import adafruit_ads1x15.ads1115 as ADS
from adafruit_ads1x15.analog_in import AnalogIn
from threading import Thread
#from multiprocessing import Process

#initialize ADS
i2c = busio.I2C(board.SCL, board.SDA)
ads = ADS.ADS1115(i2c) # Define an ADS Device class object
chan0 = AnalogIn(ads, ADS.P0)
chan1 = AnalogIn(ads, ADS.P1)
chan2 = AnalogIn(ads, ADS.P2)
chan3 = AnalogIn(ads, ADS.P3)

#Ohms per centigrade for a PT1000 probe
ohms_per_centigrade = 3.9

#Initialize global variables
version = "v0.00"
socket = socket.socket()
host = "localhost"
port = 10000
socket.bind((host, port))
temparray = []
delim = ";"
element_state = False
heat_state = False
preheat_alarm = False
element_run_time = timedelta(seconds=10) #Run time of the element in seconds
element_off_time = timedelta(seconds=10) #Amount of seconds the element has to be off
element_end_time = datetime.now() + element_run_time
element_start_time = element_end_time + element_off_time
set_pittemp = 0
temppref = "F"
socket.listen(1)

#Set output for element control
element_pin = 4 #This is BCM pin numbering
GPIO.setmode(GPIO.BCM)
GPIO.setup(element_pin, GPIO.OUT)

def calculate_voltage(ads):
	v = ads * ((4.95 - 0.83) / 32767)
	return(v)

def calculate_resistance(voltage):
	r = (voltage * 1000)/(4.95 - voltage)
	return(r)

def calculate_temp_c(r):
	ref_res = r - 1000
	c = (ref_res / ohms_per_centigrade)
	return(c)

def calculate_temp_f(c):
	f = (1.8 * c) + 32
	return(f)

def listtostring(list):
	res = ''
	for ele in list:
		res = res + str(ele) + delim
	return(res)

def check_temp():
	templist = []
	c0 = chan0.value
	c1 = chan1.value
	c2 = chan2.value
	c3 = chan3.value
	#Calculate voltage
	v0 = calculate_voltage(c0)
	v1 = calculate_voltage(c1)
	v2 = calculate_voltage(c2)
	v3 = calculate_voltage(c3)
	#Calculate resistance
	r0 = calculate_resistance(v0)
	r1 = calculate_resistance(v1)
	r2 = calculate_resistance(v2)
	r3 = calculate_resistance(v3)
	#Calculate Celcius
	c0 = calculate_temp_c(r0)
	c1 = calculate_temp_c(r1)
	c2 = calculate_temp_c(r2)
	c3 = calculate_temp_c(r3)
	if (temppref == 'C'):
		temparray = [c0, c1, c2, c3]
		templist.append(temparray)
	elif (temppref == 'F'):
		f0 = calculate_temp_f(c0)
		f1 = calculate_temp_f(c1)
		f2 = calculate_temp_f(c2)
		f3 = calculate_temp_f(c3)
		temparray = [f0, f1, f2, f3]
		templist.append(temparray)
	return(templist)

def heat():
	global element_state
	global element_end_time
	global element_start_time
	if (heat_state):
		print('HEAT ON!')
		#Element needs to be on - heat up
		if not (element_state):
			#The element was off, check previous end time
			if (element_start_time <= datetime.now()):
				#Element has been off long enough, start element
				element_state = True
				#Turn on element
				GPIO.output(element_pin, True)
				print('ELEMENT ON!')
				#Set end time for element
				element_end_time = datetime.now() + element_run_time
			else:
				#Element needs to be on, but hasn't hit run time limit/start time yet
				#Do nothing
				pass
		else:
			#Element is still on, check time
			if (element_end_time >= datetime.now()):
				#Element has run long enough, switch off
				element_state = False
				#Turn off element
				GPIO.output(element_pin, False)
				print('ELEMENT OFF!')
				#Set new start time
				element_start_time = datetime.now() + element_off_time
			else:
				#Element is still running and has time left
				#Do nothing
				pass
	else:
		if (element_state):
			#Heat needs to be off and was on
			GPIO.output(element_pin, False)
			element_state = False
			print('HEAT OFF!')
			#set minimum off time
			element_start_time = datetime.now() + element_off_time
		else:
			#Heat state is off and is still off
			#Do nothing
			pass

def check_pit_temp():
	global heat_state
	global preheat_alarm
	###Calculate temp
	c0 = chan0.value
	#Calculate voltage
	v0 = calculate_voltage(c0)
	#Calculate resistance
	r0 = calculate_resistance(v0)
	#Calculate Celcius
	c0 = calculate_temp_c(r0)
	if (temppref == 'C'):
		pittemp = c0
	elif (temppref == 'F'):
		f0 = calculate_temp_f(c0)
		pittemp = f0
	if (set_pittemp == 0):
		#Pit set to 0, set heat_state to False
		heat_state = False
	elif ((set_pittemp > 0) and (set_pittemp < pittemp)):
		#pit temp high enough
		heat_state = False
		#if preheat alarm hasn't gone off, set it to go off
		if not (preheat_alarm):
			#sound some kind of alarm, hasn't been setup yet, would do it here
			preheat_alarm = True
	elif ((set_pittemp > 0) and (set_pittemp > pittemp)):
		#pit temp too low, turn heat on
		heat_state = True


def piboss_cmd(data):
	global set_pittemp
	global preheat_alarm
	global temppref
	response = ""
	if (data):
		if (data ==  "vcheck"):
			response += version
		elif (data == "temp"):
			templist = check_temp()
			tempstring = listtostring(templist)
			response += tempstring
		elif (data[0:6] == "setpit"):
			#Get C or F Preference
			temppref = data[6]
			#set desired pit temp
			set_pittemp_array = data.split("=")
			preheat_alarm = False
			set_pittemp = int(set_pittemp_array[1])
			response += "Set Pit to " + str(set_pittemp) + temppref
			#print("set_pittemp:" + str(set_pittemp) + "; temppref:" + temppref)
		else:
			response = "unrecognized"
	else:
		response = "nodata"
	return(response)

def com_loop():
	while True:
		c, addr = socket.accept()
		data = c.recv(1024)
		if data:
			#Get data from PHP
			result = data.decode() 
			print("{} - Got data! - {}".format(datetime.now(), result))
			response = piboss_cmd(result)
			#Now send databack to PHP
			print("{} - Responding - {}".format(datetime.now(), response))
			message = response
			c.send(bytes(message,'utf-8'))
		c.close()

def temp_loop():
	while True:
		print("set_pittemp:" + str(set_pittemp) + "; temppref:" + temppref)
		check_pit_temp()
		heat()
		sleep(2)

Thread(target=com_loop).start()
temp_loop()

#if __name__ == '__main__':
#	Process(target=com_loop).start()
#	Process(target=temp_loop).start()
