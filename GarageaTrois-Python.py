#!/usr/bin/python
import RPi.GPIO as GPIO
import os
import time
import sys

GPIO.setmode(GPIO.BCM)

pin = sys.argv[1]
GPIO.setup(pin, GPIO.OUT)

GPIO.output(pin, 1)
time.sleep(0.05)
GPIO.output(pin, 0)
