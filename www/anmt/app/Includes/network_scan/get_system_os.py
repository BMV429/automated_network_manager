#!/usr/bin/python3

import re
import xml.etree.ElementTree as ET
import json
from pprint import pprint

hosts=[]

webserver_path = "/var/www/anmt/html/anmt/app/Includes/"
base_path = "/var/www/anmt/html/anmt/app/Includes/"

NMAP_INPUT=f"{base_path}/network_scan/nmap.xml"
#NMAP_INPUT="/home/sb/automated_network_manager/network_scan/nmap.xml"
WEBUI_INPUT=f"{webserver_path}/manual_hosts.json"

### ------ FROM WEBUI
with open(WEBUI_INPUT, "r") as f:
    data_json = json.load(f)
    print(data_json)
    
for record in data_json:
    print(record)
    os = record["OS"]
    ip_list_raw = record["IPv4"].strip('][\'')
    ip_list = ip_list_raw.split(',')
   
    for ip_address in ip_list:
        host = dict(ip_address = ip_address, os_vendor = os, os_family = os, os_gen = os)
        hosts.append(host)
# ----------------

### ------ FROM NMAP SCAN
## Get output from nmap.xml and put the important information in a list of hosts (dictionaries).
tree = ET.parse(NMAP_INPUT)
root = tree.getroot()

for child in root:
    if child.tag == "host":
        ip_address = os_vendor = os_family = os_gen = ""
        for child_host in child:
            if child_host.tag == "address":
                address_type = child_host.attrib.get('addrtype')
                if (address_type == 'ipv4'):
                    ip_address = child_host.attrib.get('addr')
            if child_host.tag == "os":
                i=0
                for child_os in child_host:
                    if child_os.tag == "osmatch" and i == 0:
                        i+=1
                        j=0
                        for osclass in child_os:
                            if osclass.tag == "osclass" and j == 0:
                                os_vendor = osclass.attrib.get('vendor')
                                os_family = osclass.attrib.get('osfamily')
                                os_gen = osclass.attrib.get('osgen')                                
                                j+=1
        
        host = dict(ip_address = ip_address, os_vendor = os_vendor, os_family = os_family, os_gen = os_gen)
        hosts.append(host)
        
### ----------------


for host in hosts:
    pprint(host)

