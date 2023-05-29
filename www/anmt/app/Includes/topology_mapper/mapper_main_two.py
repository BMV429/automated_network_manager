#!/usr/bin/python3

import pandas as pd
import networkx as nx
from pyvis.network import Network
import PIL.Image
from time import time
from database import Database
import requests
import sys
import urllib3
import json
from pprint import pprint
import xml.etree.ElementTree as ET
from config import * # Configuration variables.

# Get time of execution.
timestamp = time()



## FUNCTIONS
def read_json_from_file(filename):
    with open(filename, 'r') as f:
        data = json.load(f)

    return data
    
def get_router_hosts(router):
    hosts = []

    # ----- Get hosts with Cisco RESTCONF API.
    hosts = get_hosts_restconf(router, hosts)

    # ----- Get hosts with SNMP.
    #(hosts, router_id) = get_hosts_snmp(router)

    # ----- Get hosts from manual file.
    #hosts = get_hosts_manual(hosts)

    # Determine 'router_id'.
    #for host in hosts:
    #    if (instance in host['IPv4']):
    #        router_id = host['host_id']

    #for host in hosts:
    #    if (host['router_ip'] == instance):
    #        host['router_id'] = router_id

    #hosts = add_host_details(hosts)


    # Also needed for webUI inventory.
    #store_hosts(hosts)
    # To test if program works with reading from the db.
    #hosts = read_hosts(router_id)

    return hosts

def get_hosts_restconf(router, hosts):
    # Credentials
    router_ip = router['router_ip']
    router_username = router['router_username']
    router_password = router['router_password']

    # Queries.
    query_native_ip = "/data/Cisco-IOS-XE-native:native/ip" # Show default gateway! -> for route to internet (add node for internet/WAN)
    query_routing_table = "/data/ietf-routing:routing-state" # Shows default gateway - (Network IPs + PORTS + PORTNAME)
    query_arp_table = "/data/Cisco-IOS-XE-arp-oper:arp-data/arp-vrf" # ARP TABLE - (MAC + IP + PORTNAME)
    query_interface = "/data/ietf-interfaces:interfaces-state" # Get list with interfaces - (INT_ID, MAC, PORTNAME, STATUS, SPEED) (NO IP...)
    query_hw_info = "/data/Cisco-IOS-XE-device-hardware-oper:device-hardware-data/device-hardware/device-inventory"

    # Get data. (online or offline)
    router_ip_string = router_ip.split('.')
    router_ip_string = ''.join(router_ip_string)
    base_path = f'{current_directory}/app/Includes/topology_mapper'
    online = 0

    if (online):
        data_arp = execute_restconf_query(query_arp_table, router_ip, router_username, router_password)
        data_intf = execute_restconf_query(query_interface, router_ip, router_username, router_password)
        data_native_ip = execute_restconf_query(query_native_ip, router_ip, router_username, router_password)
        data_routing_table = execute_restconf_query(query_routing_table, router_ip, router_username, router_password)
        data_hw_info = execute_restconf_query(query_hw_info, router_ip, router_username, router_password)

        write_json_to_file(data_arp, f'{base_path}/data_arp_{router_ip_string}.json')
        write_json_to_file(data_intf, f'{base_path}/data_intf_{router_ip_string}.json')
        write_json_to_file(data_native_ip, f'{base_path}/data_native_ip_{router_ip_string}.json')
        write_json_to_file(data_routing_table, f'{base_path}/data_routing_table_{router_ip_string}.json')
        write_json_to_file(data_hw_info, f'{base_path}/data_hw_info_{router_ip_string}.json')
    else:
        data_arp = read_json_from_file(f'{base_path}/data_arp_{router_ip_string}.json')
        data_intf = read_json_from_file(f'{base_path}/data_intf_{router_ip_string}.json')
        data_native_ip = read_json_from_file(f'{base_path}/data_native_ip_{router_ip_string}.json')
        data_routing_table = read_json_from_file(f'{base_path}/data_routing_table_{router_ip_string}.json')
        data_hw_info = read_json_from_file(f'{base_path}/data_hw_info_{router_ip_string}.json')

    # Specify tables.
    arp_table = data_arp['Cisco-IOS-XE-arp-oper:arp-vrf'][0]['arp-entry']
    intf_table = data_intf['ietf-interfaces:interfaces-state']['interface']
    routing_table = data_routing_table['ietf-routing:routing-state']['routing-instance'][0]['ribs']['rib'][0]['routes']['route']

    # Get router gateway and DNS. (method 1)
    router_gateway = data_native_ip['Cisco-IOS-XE-native:ip']['route']['ip-route-interface-forwarding-list'][0]['fwd-list'][0]['fwd']
    router_dns = data_native_ip['Cisco-IOS-XE-native:ip']['name-server']['no-vrf']

    # Get router gateway and DNS. (method 2) (best method)
    router_gateway = routing_table[0]['next-hop']['next-hop-address'] # Assuming ['route'][0] is always the default route. Otherwise use ['route'] and use a for loop to check destination-prefix == '0.0.0.0/0'.
    router_dns = ''
    
    # Get device model and serial numbers.
    device_model = data_hw_info['Cisco-IOS-XE-device-hardware-oper:device-inventory'][0]['hw-description']
    serial_number = data_hw_info['Cisco-IOS-XE-device-hardware-oper:device-inventory'][0]['serial-number']
    
    all_macs = []
    all_ips = []

### Create host record for router.
    ip_list = []
    mac_list = []

    # INTERFACES gives PORT + MAC
    for intf in data_intf['ietf-interfaces:interfaces-state']['interface']:
        portname = intf['name']
        admin_status = intf['admin-status'] # If admin_status == 'up' -> turn link green? if down -> turn link red.
        operation_status = intf['oper-status'] # If admin_status == 'up' -> turn link green? if down -> turn link red. 
        link_speed = intf['speed'] # In bits/sec.
        mac_addr = intf['phys-address']

        if mac_addr not in mac_list:
            mac_list.append(mac_addr)
            all_macs.append(mac_addr)

    # ARP TABLE gives IP + MAC.
    for arp_record in arp_table:
        if arp_record['hardware'] in mac_list:
            ip_list.append(arp_record['address'])
            all_ips.append(arp_record['address'])
    
    hostname = router_ip
    router_host = dict(MAC = mac_list, IPv4 = ip_list, port = portname, hostname = hostname, gateway = router_gateway, dns_server = router_dns, serial_number = serial_number, device_model = device_model, router_ip = router_ip)
    hosts.append(router_host)
### ------------------------------

### Create host records for attached devices.

    # ARP TABLE gives IP + MAC.
    for arp_record in arp_table:
        ip_list = []
        mac_list = []

        ip_addr = arp_record['address']
        mac_addr = arp_record['hardware']
        portname = arp_record['interface']
        hostname = ip_addr

        if (ip_addr not in all_ips) and (mac_addr not in all_macs) and (mac_addr != '00:00:00:00:00:00'):

            mac_list.append(mac_addr)
            ip_list.append(ip_addr)

            all_macs.append(mac_addr)
            all_ips.append(ip_addr)
            
            router_host = dict(MAC = mac_list, IPv4 = ip_list, port = portname, hostname = hostname, gateway = "", dns_server = "", serial_number = "", device_model = "", router_ip = router_ip)
            hosts.append(router_host)
### ------------------------------

    return hosts 


if __name__ == ("__main__"):
    # Get routers credentials.
    routers = read_json_from_file(sys.argv[1])

    # Create individual maps per router.
    for router in routers:
        # Define variables.
        router_ip = router['router_ip']

        router_ip_string = router_ip.split('.')
        router_ip_string = '_'.join(router_ip_string)

        time_string = str(timestamp).split('.')
        time_string = '_'.join(time_string)

        base_path = f'{current_directory}/app/Includes/topology_mapper'
        TOPOLOGY_PATH_SINGLE = f'{current_directory}/public/topology_{router_ip_string}_{time_string}.html'

        topology_map = nx.Graph()

        # Start gathering.
        print(f'\n\nGetting hosts from router: {router_ip}')
        hosts = get_router_hosts(router)
        
        pprint(hosts)








