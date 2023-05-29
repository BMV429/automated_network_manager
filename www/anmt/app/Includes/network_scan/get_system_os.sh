#!/bin/bash

###
#	This script scans all subnets in the subnets.lst file and executes the python script that formats the output.
###

#sudo nmap -sU -O -oX /home/sb/nmap.xml -p161 --script snmp-brute --script-args snmplist=community.lst 10.0.0.0/24
sudo nmap -O -oX /home/sb/automated_network_manager/network_scan/nmap.xml -iL /home/sb/automated_network_manager/network_scan/subnets.lst
#sudo nmap -O -oX /home/sb/network_scan/nmap.xml -iL /home/sb/network_scan/subnets.lst
#python3 /home/sb/network_scan/get_snmp_host.py

#$(pwd)
