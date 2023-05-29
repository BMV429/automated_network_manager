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

from pprint import pprint # DEBUG.
#import matplotlib.pyplot as plt

### --- NOTES --- ###
# usage of database:
# with Database() as db:
#   db.execute(query)
#
# One file stores hosts -> one file reads latest hosts from db and displays it?
#
# get_hosts_snmp() In English:
# Go over ARP list (MAC <-> IP), and for every record in this list, look through the MAC list (MAC <-> port) for a match.
# Create a 'host' record that combines MAC + IP + PORT in a single record. (Every 'host' is a seperate dictionary)
# Put all the 'host' dictionaries in a single 'hosts' list.
###               ###

DOMAIN_NAME = 'anmt'
TOPOLOGY_PATH = f'/var/www/{DOMAIN_NAME}/html/anmt/app/Includes/current_topology.html'

def execute_query(query):
    prometheus_ip = '10.0.1.30'
    base_url = f'http://{prometheus_ip}:9090/api/v1/query?query='
    url = f'{base_url}{query}'
    
    r = requests.get(url = url)
    data = r.json()

    # Retry on failure.
    retries = 0
    if (data['status']!='success') and (retries < 3):
        r = requests.get(url = url)
        data = r.json()
        retries += 1
        print("Prometheus query failed. Retrying...")
    elif (retries >= 3):
        print("Prometheus could not be reached.")
        exit()

    print(f'\n\n{query}')
    #pprint(data)
    return data

def execute_restconf_query(query, instance):

    # ----- Get hosts with Cisco RESTCONF (API).
    cisco_user = cisco_pass = cisco_port = ''
    cisco_user = sys.argv[2] ## Create a seperate user just for this script... (for security)
    cisco_pass = sys.argv[3]
    cisco_port = '443'

    # Use prompt for password if not entered with command.
    if (cisco_pass == ''):
        cisco_pass = input("Enter your Cisco password")

    # Disable SSL Warnings.
    urllib3.disable_warnings(urllib3.exceptions.InsecureRequestWarning)

    # Create the base URL for RESTCONF calls.
    url_base = "https://{h}:{p}/restconf".format(h=instance, p=cisco_port)

    # Identify yang+json as the data formats.
    headers = {'Content-Type': 'application/yang-data+json',
            'Accept': 'application/yang-data+json'}


    url = f'{url_base}{query}'

    # Make GET request.
    response = requests.get(url,
                            auth=(cisco_user, cisco_pass),
                            headers=headers,
                            verify=False
                            )

    response_json = response.json()

    return response_json

def get_hosts_restconf(instance):
    query = "/data/Cisco-IOS-XE-native:native/interface" # Get list with interfaces (Interface IP + Index + netmask)
    query = "/data/ietf-interfaces:interfaces/interface=GigabitEthernet3/ietf-ip:ipv4" #/neighbor does not work ... -> if it did we could loop over interfaces from query1 and fill it in interface=GigabitEthernet{n}
    query = "/data/ietf-interfaces:interfaces" # (IP, portname, status)query = "/data/Cisco-IOS-XE-matm-oper:matm-oper-data"
    query = "/data/netconf-state/capabilities"  # Shows all the yang modules this device can use!!

    query_native_ip = "/data/Cisco-IOS-XE-native:native/ip" # Show default gateway! -> for route to internet (add node for internet/WAN)
    query_routing_table = "/data/ietf-routing:routing-state" # Shows default gateway! (Network IPs + Ports + portname)
    query_arp_table = "/data/Cisco-IOS-XE-arp-oper:arp-data/arp-vrf" # ARP TABLE! (MAC + IP + Portname)
    query_interface = "/data/ietf-interfaces:interfaces-state" # Get list with interfaces (Index, MAC, portname, status, speed) (NO IP...)
    query_hw_info = "/data//Cisco-IOS-XE-device-hardware-oper:device-hardware-data/device-hardware/device-inventory"

    data_arp = execute_restconf_query(query_arp_table, instance)
    data_intf = execute_restconf_query(query_interface, instance)
    data_native_ip = execute_restconf_query(query_native_ip, instance)
    data_routing_table = execute_restconf_query(query_routing_table, instance)
    data_hw_info = execute_restconf_query(query_hw_info, instance)

    arp_table = data_arp['Cisco-IOS-XE-arp-oper:arp-vrf'][0]['arp-entry']
    intf_table = data_intf['ietf-interfaces:interfaces-state']['interface']
    routing_table = data_routing_table['ietf-routing:routing-state']['routing-instance'][0]['ribs']['rib'][0]['routes']['route']

    # Get router gateway and DNS. (method 1)
    gw = data_native_ip['Cisco-IOS-XE-native:ip']['route']['ip-route-interface-forwarding-list'][0]['fwd-list'][0]['fwd']
    dns_server = data_native_ip['Cisco-IOS-XE-native:ip']['name-server']['no-vrf']
    print('\n\ndata_native_ip')
    print(f'{gw} --- {dns_server}')
    print('\n\n\n')

    # Get router gateway and DNS. (method 2) (best method)
    gw = routing_table[0]['next-hop']['next-hop-address'] # Assuming ['route'][0] is always the default route. Otherwise use ['route'] and use a for loop to check destination-prefix == '0.0.0.0/0'.
    print(gw)

    device_model = data_hw_info['Cisco-IOS-XE-device-hardware-oper:device-inventory'][0]['hw-description']
    serial_number = data_hw_info['Cisco-IOS-XE-device-hardware-oper:device-inventory'][0]['serial-number']

    #pprint(data_intf)
    
    host_id = 0
    hosts = []
    router_id = ''
    used_macs = []
    used_ips = []


    ### Create host record for router.
    ip_list = []
    mac_list = []
    for intf in data_intf['ietf-interfaces:interfaces-state']['interface']:
        pprint(intf)
        print('\n')
        admin_status = intf['admin-status'] # If admin_status == 'up' -> turn link green? if down -> turn link red. or is this intf['oper-status']?
        link_speed = intf['speed'] # In bits/sec.
        mac_addr = intf['phys-address']
        portname = intf['name']

        mac_list.append(mac_addr)
        used_macs.append(mac_addr)


    for arp_record in arp_table:
        if arp_record['hardware'] in mac_list:
            ip_list.append(arp_record['address'])
            used_ips.append(arp_record['address'])


    
    hostname = instance
    router_host = dict(host_id = host_id, MAC = mac_list, IPv4 = ip_list, port = portname, hostname = hostname, gateway = gw, dns_server = dns_server, serial_number = serial_number, device_model = device_model)
    hosts.append(router_host)
    host_id += 1
    ###
    


    ## Parse ARP table in hosts list.

    # Parse ARP table.
    for arp_record in arp_table:
        ip_list = []
        mac_list = []

        print(f"{arp_record['address']}\n{arp_record['hardware']}\n{arp_record['interface']}\n")
        print('\n\n')

        ip_addr = arp_record['address']
        mac_addr = arp_record['hardware']
        portname = arp_record['interface']
        hostname = ip_addr

        if (ip_addr not in used_ips) and (mac_addr not in used_macs) and (mac_addr != '00:00:00:00:00:00'):

            mac_list.append(mac_addr)
            ip_list.append(ip_addr)
            used_macs.append(mac_addr)
            used_ips.append(ip_addr)
            host = dict(host_id = host_id, MAC = mac_list, IPv4 = ip_list, port = portname, hostname = hostname, gateway = "", dns_server = "", serial_number = "", device_model = "")
            hosts.append(host)
            host_id += 1


    hosts = add_hostname_details(hosts)

    # Determine 'router_id'.
    for host in hosts:
        if (instance in host['IPv4']):
            router_id = host['host_id']


    print(router_id)

    return hosts, router_id 

# Put every subloop in a seperate function, so it looks less bad.
def get_hosts_snmp(instance):
    query = 'atPhysAddress{instance="' + instance + '"}'
    data_arp = execute_query(query)

    # Get IP addresses of devices (the ones in arp table are broken).
    query = 'ifPhysAddress{ifPhysAddress!="00:00:00:00:00:00"}'
    data_ips = execute_query(query)

    # Get system hostnames.
    query = 'sysName'
    data_hostnames = execute_query(query)


    # Put host (IP / MAC / port) in a dictionary. (Success)
    hosts = []
    iplist=[]
    maclist=[]
    host_id = 0
    router_id = "" # new
    
    for arp_metric in data_arp['data']['result']:
        mac_list = []
        ip_list = []

        for metric in data_ips['data']['result']:
            arp_MAC = arp_metric['metric']['atPhysAddress']
            arp_port = arp_metric['metric']['atIfIndex']
            MAC = metric['metric']['ifPhysAddress']
            IP = metric['metric']['instance']

            #print(f"MAC, arp_MAC = {MAC}, {arp_MAC}")

            if (arp_MAC == MAC):
                if (IP in iplist):
                    print(f"IP {IP} already in ip list.")
                    print(iplist)
                    for ho in hosts:
                        if (IP in ho['IPv4']) and MAC not in ho['MAC']:
                            ho['MAC'].append(MAC) # If IP is already in the hosts dictionary -> add MAC address to the host record.
                            iplist.append(IP)
                            maclist.append(MAC)
                elif (MAC in maclist):
                    print(f"MAC {MAC} already in mac list.")
                    print(maclist)
                    for ho in hosts:
                        if (MAC in ho['MAC']) and IP not in ho['IPv4']:
                            ho['IPv4'].append(IP) # If MAC address is already bound to a host in the hosts dictionary -> add IP address to the host.
                            iplist.append(IP)
                            maclist.append(MAC)

                else: # If neither the MAC or IP has been found in hosts dictionary -> create a new record.
                    print(f"Added new record. ({IP}, {MAC}, {arp_port})")
                    mac_list.append(MAC)
                    ip_list.append(IP)
                    iplist.append(IP)
                    maclist.append(MAC)
                    host = dict(host_id = host_id, MAC = mac_list, IPv4 = ip_list, port = arp_port, gateway = "", dns_server = "", serial_number = "", device_model = "")
                    host_id += 1
                    hosts.append(host)
        
    # Add hostnames to the hosts dictionary.
    for metric in data_hostnames['data']['result']:
        ip = metric['metric']['instance']
        hostname = metric['metric']['sysName']

        for host in hosts:
            if (ip in host['IPv4']):
                host['hostname'] = hostname

    # Get router id.
    for host in hosts:
        if (instance in host["IPv4"]):
            router_id = host["host_id"]
            print(f'router_id={router_id}')
            host["port"] = ""
    #pprint(hosts)


    # --- DEBUG
    for x in hosts:
        pprint(x)
    ###
    print(instance)
    return hosts, router_id

def add_hostname_details(hosts):
    # Get IP addresses of devices (the ones in arp table are broken).
    query = 'ifPhysAddress{ifPhysAddress!="00:00:00:00:00:00"}'
    data_ips = execute_query(query)

    # Get system hostnames.
    query = 'sysName'
    data_hostnames = execute_query(query)

    padded_hosts = []

    for metric in data_ips['data']['result']:
        MAC = metric['metric']['ifPhysAddress']
        IP = metric['metric']['instance']

        for host in hosts:
            if (IP in host['IPv4']):
                for metric2 in data_hostnames['data']['result']:
                    if (IP == metric2['metric']['instance']):
                        host['hostname'] = metric2['metric']['sysName']
                        print(host['hostname']) # Debug.
        
    return hosts



def get_hosts_manual(hosts):

    WEBUI_INPUT=f'/var/www/{DOMAIN_NAME}/html/anmt/app/Includes/manual_hosts.json'
    #WEBUI_INPUT="manual_devices.json"

    with open(WEBUI_INPUT, "r") as f:
        data_json = json.load(f)
        #print(data_json)

    host_id = len(hosts)

    # Get existing host IDs.
    used_host_ids = []
    for host in hosts:
        used_host_ids.append(host['host_id'])

        
    for record in data_json:
        print(record)

        # Check if host_id already exists or not ...
        print('printing used_host_ids...')
        print(used_host_ids)
        while (host_id in used_host_ids):
            host_id += 1

        used_host_ids.append(host_id)

        os = hostname = portname = gateway = dns_server = sn = device_model = ""
        
        os = record["OS"]
        portname = record["connected_device_port"]
        sn = record["sn"]

        ip_list_raw = record["IPv4"].strip('][\'')
        ip_list = ip_list_raw.split(',')

        mac_list_raw = record["MAC"].strip('][\'')
        mac_list = mac_list_raw.split(',')

        hostname = ip_list[0]

        device_model = record["model"]

        # Check if record["connected_device_ip"] is equal to instance

        host = dict(host_id = host_id, MAC = mac_list, IPv4 = ip_list, port = portname, hostname = hostname, gateway = gateway, dns_server = dns_server, serial_number = sn, device_model = device_model)
        
        
        print(host)
        hosts.append(host)

    return hosts

def store_hosts(hosts, router_id):
    with Database() as db:
        print('Storing hosts.')
        timestamp = time()
        print(timestamp)

        for host in hosts:
            # Clear variables.
            host_id = hostname = ip_list = mac_list = port = gateway = dns_server = serial_number = device_model = ""

            host_id = host["host_id"]
            hostname = host["hostname"]
            ip_list = host["IPv4"]
            mac_list = host["MAC"]
            port = host["port"]
            gateway = host["gateway"]
            dns_server = host["dns_server"]
            serial_number = host["serial_number"]
            device_model = host["device_model"]
        
            query = "INSERT INTO hosts (host_id, hostname, ip_list, mac_list, port, router_id, timestamp, default_gateway, dns_server, serial_number, device_model) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"

            data = (host_id, hostname, str(ip_list), str(mac_list), port, router_id, str(timestamp), str(gateway), str(dns_server), str(serial_number), str(device_model))

            db.execute(query, data)


def read_hosts(router_id):
    # Read the database and give a hosts dictionary back for the specified router ID.

    with Database() as db:
        print('Reading hosts.')
        hosts = []

        rows = db.get_latest_hosts()
        #print(rows)

        for row in rows:
            host = dict(host_id = row[0], hostname = row[1], IPv4 = row[2], MAC = row[3], port = row[4], router_id = row[5], timestamp = row[6], gateway = row[7], dns_server = row[8], serial_number = row[9], device_model = row[10])

            hosts.append(host)

        print(hosts)
        return hosts


def add_nodes(hosts):
    for host in hosts:
        g_router_a.add_node(host["host_id"], label=f'{host["hostname"]}', title=f'ID: {host["host_id"]}\nHostname: {host["hostname"]}\nIPv4: {host["IPv4"]}\nMAC: {host["MAC"]}\nSerial number: {host["serial_number"]}\nDevice model: {host["device_model"]}')


def add_edges(hosts, router_id): #router_ip = instance
    # Check how many devices are connected per router port.
    devices_per_port=dict()
    for host in hosts:
        port=host["port"]
        if (devices_per_port.get(port)):
            devices_per_port[port] += 1
        else:
            devices_per_port[port] = 1

    print(devices_per_port)
    # Add seperate switch, if more than 1 connection per router port.
    for port in devices_per_port:
        if (devices_per_port[port] > 1) and (port != ''):
            node_name=f'switch_a_{port}'
            g_router_a.add_node(node_name, color='red', title='vSwitch', shape='image', image='icons/switch.png', label=' ')
            g_router_a.add_edge(node_name, router_id, label=f'{port}')

    # Create edges.
    for host in hosts:
        attached_device_id = host["host_id"]
        port = host["port"]
        

        if ((devices_per_port[port]) > 1):
            switch_name=f'switch_a_{port}'
            g_router_a.add_edge(switch_name, attached_device_id)
        else:
            if (port != ''):
                g_router_a.add_edge(router_id, attached_device_id, label=f'{port}')


#def main():
## Queries
# Get ARP table.
# instance = '10.0.0.5'
instance = sys.argv[1]


### Get a list of all hosts connected to router A.
hosts = []


# ----- Get hosts with Cisco RESTCONF API
(hosts, router_id) = get_hosts_restconf(instance)
# -----------

# ----- Get hosts with SNMP.
#(hosts, router_id) = get_hosts_snmp(instance)
# -----------

# ----- Get hosts from manual file.
hosts = get_hosts_manual(hosts)
# -----------

print('\n\n\n\n')
pprint(hosts)




#sys.exit()

### Universal part.
store_hosts(hosts, router_id)

# To test if program works with reading from the db.
hosts = read_hosts(router_id)

# Create a sub-graph of router_a (every router will become its own graph so you can link all routers in one big graph?)
g_router_a = nx.Graph()

# Add a node for the router.
for host in hosts:
    if (router_id == host["host_id"]):
        g_router_a.add_node(router_id, label=host["hostname"], color='green', title=f'ID: {host["host_id"]}\nHostname: {host["hostname"]}\nIPv4: {host["IPv4"]}\nMAC: {host["MAC"]}\nSerial number: {host["serial_number"]}\nDevice model: {host["device_model"]}', shape='image', image='icons/router.png')

# Add a node for every host.
add_nodes(hosts)

## Connect the hosts with the router. (Add connections)
add_edges(hosts, router_id)

print(f'# of hosts: {g_router_a.number_of_nodes()}')


OPTIONS = """
var options = {
    "layout": {
        "hierarchical": {
            "enabled": true
        }
    }
}
"""



# Create schema.
net = Network(notebook=True)
net.from_nx(g_router_a)
#net.show_buttons(filter_=["layout", "physics"])
net.set_options(OPTIONS)
net.show(TOPOLOGY_PATH)



#if __name__ == ("__main__"):
    
    #main()
