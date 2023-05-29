#!/usr/bin/python3

import re
import xml.etree.ElementTree as ET
import json

### --- Notes ---
# This script is executed on 2 times.
#   After an NMAP scan has been executed (with the cron job).
#   After someone manually adds a device with on the webui.

hosts=[]

webserver_path = "/var/www/anmt/html/anmt/app/Includes/"
repository_path = "/home/sb/automated_network_manager"

NMAP_INPUT=f"{repository_path}/network_scan/nmap.xml"
WEBUI_INPUT=f"{webserver_path}/manual_hosts.json"
KEYSCAN_FILE=f"{repository_path}/network_scan/keyscan.txt"
HOSTS_FILE=f"{repository_path}/network_scan/hosts.txt"
TARGETS_FILE=f"{repository_path}/network_scan/target.json"
TARGETS_FILE_LINUX=f"{repository_path}/network_scan/targetlinux.json"
TARGETS_FILE_CISCO=f"{repository_path}/network_scan/targetcisco.json"
TARGETS_FILE_WINDOWS=f"{repository_path}/network_scan/targetwindows.json"
TARGETS_FILE_PFSENSE=f"{repository_path}/network_scan/targetpfsense.json"

### ------ FROM WEBUI
with open(WEBUI_INPUT, "r") as f:
    data_json = json.load(f)
    #print(data_json)
    
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






with open(KEYSCAN_FILE, "w") as f:

    for host in hosts:
        host_ip = host.get("ip_address")
        f.write(f"\n{host_ip}")
    f.write("\n\n")        
        

## Write hosts to hosts.txt.
with open(HOSTS_FILE, "w") as f:
    #host_group_options = ["Linux", "Windows", "FreeBSD", "Cisco"]
    
    for host in hosts:
        host_ip = host.get("ip_address")
        f.write(f"\n{host_ip}")
    
    f.write("\n\n")
    
    host_group_options = []
    for host in hosts:
        host_group_option = host.get("os_family")
        
        if (host_group_option == ""):
            host_group_option = "unassigned"
        
        if (host_group_option not in host_group_options):
            host_group_options.append(host_group_option)

    for option in host_group_options:
        f.write("\n\n")
        f.write(f"[{option}]")
            
        for host in hosts:
            host_group = host.get("os_family")
            if (host_group == ""):
                host_group = "unassigned"

            if (host_group == option):
                host_ip = host.get("ip_address")
                f.write(f"\n{host_ip}")


## Create target.json from hosts list.
ip_list = []
with open(TARGETS_FILE, "w") as f:
        f.write('[\n')
        for host in hosts:
            ip_address = host.get("ip_address")
            ip_list.append(ip_address)

        for ip_address in ip_list:
            f.write('\n\t{\n\t\t"targets": ["' + ip_address + '"],')
            f.write('\n\t\t"labels": {"job": "node"}\n\t}')
            if (ip_address != ip_list[-1]):
                f.write(',')
        f.write('\n]')

#with open("ip.txt", "w") as f:
#        f.write('\n')
#        for ip_record in ip_list:
#            f.write(ip_record)
#            if (ip_record != ip_list[-1]):
#                f.write('\n')

# LINUX
with open(TARGETS_FILE_LINUX, "w") as f:
    f.write('[\n')
    for host in hosts:
        if host['os_family'] == 'Linux':
                f.write('\n\t{\n\t\t"targets": ["'  + host['ip_address'] +  '"],')
                f.write('\n\t\t"labels": {"job": "node"}\n\t}')
                f.write(',') 
    f.write('\n]')

with open(TARGETS_FILE_LINUX, 'r') as file:
    text = file.read()

last_comma_index = text.rfind(',')
text = text[:last_comma_index] + text[last_comma_index+1:]

with open(TARGETS_FILE_LINUX, 'w') as file:
    file.write(text)

    
# CISCO
with open(TARGETS_FILE_CISCO, "w") as f:
    f.write('[\n')
    for host in hosts:
        if host['os_family'] == 'IOS':
                f.write('\n\t{\n\t\t"targets": ["'  + host['ip_address'] +  '"],')
                f.write('\n\t\t"labels": {"job": "node"}\n\t}')
                f.write(',')                              
    f.write('\n]')

with open(TARGETS_FILE_CISCO, 'r') as file:
    text = file.read()

last_comma_index = text.rfind(',')
text = text[:last_comma_index] + text[last_comma_index+1:]

with open(TARGETS_FILE_CISCO, 'w') as file:
    file.write(text)

    
    
# WINDOWS
with open(TARGETS_FILE_WINDOWS, "w") as f:
    f.write('[\n')
    for host in hosts:
        if host['os_family'] == 'Windows':
                f.write('\n\t{\n\t\t"targets": ["'  + host['ip_address'] +  '"],')
                f.write('\n\t\t"labels": {"job": "node"}\n\t}')
                f.write(',')   
    f.write('\n]')

with open(TARGETS_FILE_WINDOWS, 'r') as file:
    text = file.read()

last_comma_index = text.rfind(',')
text = text[:last_comma_index] + text[last_comma_index+1:]

with open(TARGETS_FILE_WINDOWS, 'w') as file:
    file.write(text)

    
    
# PFSENSE
with open(TARGETS_FILE_PFSENSE, "w") as f:
    f.write('[\n')
    for host in hosts:
        if host['os_family'] == 'FreeBSD':
                f.write('\n\t{\n\t\t"targets": ["'  + host['ip_address'] +  '"],')
                f.write('\n\t\t"labels": {"job": "node"}\n\t}')
                f.write(',')  
    f.write('\n]')

with open(TARGETS_FILE_PFSENSE, 'r') as file:
    text = file.read()

last_comma_index = text.rfind(',')
text = text[:last_comma_index] + text[last_comma_index+1:]

with open(TARGETS_FILE_PFSENSE, 'w') as file:
    file.write(text)
