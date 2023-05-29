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
from config import * # Global configs.
import xml.etree.ElementTree as ET
import matplotlib



# EXECUTE QUERIES.
timestamp = time()

def get_os():
    hosts=[]
    
    webserver_path = "/var/www/html/app/Includes/"
    base_path = "/var/www/html/app/Includes/"

    NMAP_INPUT=f"/home/sb/automated_network_manager/network_scan/nmap.xml"
    NMAP_INPUT=f"{base_path}/network_scan/nmap.xml"
    WEBUI_INPUT=f"{webserver_path}/manual_hosts.json"

    ### ------ FROM WEBUI
    #with open(WEBUI_INPUT, "r") as f:
    #    data_json = json.load(f)
        #print(data_json)
        
    #for record in data_json:
    #    print(record)
    ##    os = record["OS"]
    #    ip_list_raw = record["IPv4"].strip('][\'')
    #    ip_list = ip_list_raw.split(',')
    
    #    for ip_address in ip_list:
    #        host = dict(ip_address = ip_address, os_vendor = os, os_family = os, os_gen = os)
    #        hosts.append(host)
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
    return hosts

def execute_snmp_query(query):
    #prometheus_ip = '10.0.1.30'
    prometheus_ip = 'anmt'
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

    return data

def execute_restconf_query(query, instance, username, password):

    # ----- Get hosts with Cisco RESTCONF (API).
    cisco_port = '443'

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
                            auth=(username, password),
                            headers=headers,
                            verify=False
                            )

    response_json = response.json()

    return response_json

# OBSOLETE FUNCTIONS.
def get_hosts_snmp(instance):
    query = 'atPhysAddress{instance="' + instance + '"}'
    data_arp = execute_snmp_query(query)

    # Get IP addresses of devices (the ones in arp table are broken).
    query = 'ifPhysAddress{ifPhysAddress!="00:00:00:00:00:00"}'
    data_ips = execute_snmp_query(query)

    # Get system hostnames.
    query = 'sysName'
    data_hostnames = execute_snmp_query(query)


    # Put host (IP / MAC / port) in a dictionary. (Success)
    hosts=[]
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
                    #print(iplist)
                    for ho in hosts:
                        if (IP in ho['IPv4']) and MAC not in ho['MAC']:
                            ho['MAC'].append(MAC) # If IP is already in the hosts dictionary -> add MAC address to the host record.
                            iplist.append(IP)
                            maclist.append(MAC)
                elif (MAC in maclist):
                    print(f"MAC {MAC} already in mac list.")
                    #print(maclist)
                    for ho in hosts:
                        if (MAC in ho['MAC']) and IP not in ho['IPv4']:
                            ho['IPv4'].append(IP) # If MAC address is already bound to a host in the hosts dictionary -> add IP address to the host.
                            iplist.append(IP)
                            maclist.append(MAC)

                else: # If neither the MAC or IP has been found in hosts dictionary -> create a new record.
                    #print(f"Added new record. ({IP}, {MAC}, {arp_port})")
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

    # --- DEBUG
    #for x in hosts:
    #    pprint(x)
    ###

    print(instance)
    return hosts, router_id

def add_host_details(hosts):
    # Get system hostnames.
    query = 'sysName'
    #data_hostnames = execute_snmp_query(query)

    #for host in hosts:
    #    for metric in data_hostnames['data']['result']:
    #        if metric['metric']['instance'] in host['IPv4']:
    #            host['hostname'] = metric['metric']['sysName']

    # Get OS.
    operating_systems = get_os()

    for host in hosts:
        for os in operating_systems:
            if os['ip_address'] in host['IPv4']:
                host['device_os'] = os['os_family']
            
            
        



    #query = 'entPhysicalSerialNumber'
    #data_serial_no = execute_snmp_query(query)       
    return hosts

def write_json_to_file(data, filename):
    with open(filename, 'w') as f:
        json.dump(data, f, indent=4)

def read_json_from_file(filename):
    with open(filename, 'r') as f:
        data = json.load(f)

    return data

def get_hosts_manual(router, hosts):
    router_ip = router['router_ip']
    WEBUI_INPUT=f'{current_directory}/app/Includes/manual_hosts.json'

    with open(WEBUI_INPUT, "r") as f:
        data_json = json.load(f)

    pprint(data_json)

    host_id = len(hosts)

    # Get existing host IDs.
    used_host_ids = []
    for host in hosts:
        used_host_ids.append(host['host_id'])

        
    print('-------- MANUAL HOSTS --------')
    for record in data_json:
        # Check if host_id already exists or not ...

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


        host_router_ip = record["connected_device_ip"]
        # Check if record["connected_device_ip"] is equal to instance
        host = dict(host_id = host_id, MAC = mac_list, IPv4 = ip_list, port = portname, hostname = hostname, gateway = gateway, dns_server = dns_server, serial_number = sn, device_model = device_model, router_ip = host_router_ip)

        ### DEBUG.
            
        #print(host)
        print(f'router_ips -> {host["router_ip"]} - {router_ip}')

        if host['router_ip'] == router_ip:
            hosts.append(host)
        
    print('-------- ˆˆˆˆˆˆˆˆˆˆˆˆˆˆ --------')

    return hosts

# STORE / READ HOSTS TO DATABASE.
def read_hosts(router_id):
    # Read the database and give a hosts dictionary back for the specified router ID.

    with Database() as db:
        print('Reading hosts.')
        hosts = []

        rows = db.get_latest_hosts()

        for row in rows:
            host = dict(host_id = row[0], hostname = row[1], IPv4 = row[2], MAC = row[3], port = row[4], router_id = row[5], timestamp = row[6], gateway = row[7], dns_server = row[8], serial_number = row[9], device_model = row[10])

            hosts.append(host)

        return hosts

def store_hosts(hosts):
    with Database() as db:
        print('Storing hosts.')

        for host in hosts:
            # Clear variables.
            host_id = hostname = ip_list = mac_list = port = gateway = dns_server = serial_number = device_model = router_ip = ""

            host_id = host["host_id"]
            hostname = host["hostname"]
            ip_list = host["IPv4"]
            mac_list = host["MAC"]
            port = host["port"]
            gateway = host["gateway"]
            dns_server = host["dns_server"]
            serial_number = host["serial_number"]
            device_model = host["device_model"]
            router_ip = host['router_ip']
            device_os = ''
            device_os = host.get('device_os', '')

            if router_ip in ip_list:
                #router_ip = ''
                port = ''
                        
            query = "INSERT INTO hosts (host_id, hostname, ip_list, mac_list, port, router_id, timestamp, default_gateway, dns_server, serial_number, device_model, operating_system) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"

            data = (host_id, hostname, str(ip_list), str(mac_list), port, str(router_ip), str(timestamp), str(gateway), str(dns_server), str(serial_number), str(device_model), str(device_os))

            db.execute(query, data)

# GET HOSTS.
def get_hosts_restconf(router, hosts):
    instance = router['router_ip']
    username = router['router_username']
    password = router['router_password']

    query_native_ip = "/data/Cisco-IOS-XE-native:native/ip" # Show default gateway! -> for route to internet (add node for internet/WAN)
    query_routing_table = "/data/ietf-routing:routing-state" # Shows default gateway - (Network IPs + PORTS + PORTNAME)
    query_arp_table = "/data/Cisco-IOS-XE-arp-oper:arp-data/arp-vrf" # ARP TABLE - (MAC + IP + PORTNAME)
    query_interface = "/data/ietf-interfaces:interfaces-state" # Get list with interfaces - (INT_ID, MAC, PORTNAME, STATUS, SPEED) (NO IP...)
    
    query_hw_info = "/data/Cisco-IOS-XE-device-hardware-oper:device-hardware-data/device-hardware/device-inventory"

    instance_string = instance.split('.')
    instance_string = ''.join(instance_string)
    base_path = f'{current_directory}/app/Includes/topology_mapper'

    online = 0
    if (online):
        data_arp = execute_restconf_query(query_arp_table, instance, username, password)
        data_intf = execute_restconf_query(query_interface, instance, username, password)
        data_native_ip = execute_restconf_query(query_native_ip, instance, username, password)
        data_routing_table = execute_restconf_query(query_routing_table, instance, username, password)
        data_hw_info = execute_restconf_query(query_hw_info, instance, username, password)

        write_json_to_file(data_arp, f'{base_path}/data_arp_{instance_string}.json')
        write_json_to_file(data_intf, f'{base_path}/data_intf_{instance_string}.json')
        write_json_to_file(data_native_ip, f'{base_path}/data_native_ip_{instance_string}.json')
        write_json_to_file(data_routing_table, f'{base_path}/data_routing_table_{instance_string}.json')
        write_json_to_file(data_hw_info, f'{base_path}/data_hw_info_{instance_string}.json')
    else:
        data_arp = read_json_from_file(f'{base_path}/data_arp_{instance_string}.json')
        data_intf = read_json_from_file(f'{base_path}/data_intf_{instance_string}.json')
        data_native_ip = read_json_from_file(f'{base_path}/data_native_ip_{instance_string}.json')
        data_routing_table = read_json_from_file(f'{base_path}/data_routing_table_{instance_string}.json')
        data_hw_info = read_json_from_file(f'{base_path}/data_hw_info_{instance_string}.json')

    arp_table = data_arp['Cisco-IOS-XE-arp-oper:arp-vrf'][0]['arp-entry']
    intf_table = data_intf['ietf-interfaces:interfaces-state']['interface']
    routing_table = data_routing_table['ietf-routing:routing-state']['routing-instance'][0]['ribs']['rib'][0]['routes']['route']

    # Get router gateway and DNS. (method 1)
    gw = data_native_ip['Cisco-IOS-XE-native:ip']['route']['ip-route-interface-forwarding-list'][0]['fwd-list'][0]['fwd']
    dns_server = data_native_ip['Cisco-IOS-XE-native:ip']['name-server']['no-vrf']

    # Get router gateway and DNS. (method 2) (best method)
    gw = routing_table[0]['next-hop']['next-hop-address'] # Assuming ['route'][0] is always the default route. Otherwise use ['route'] and use a for loop to check destination-prefix == '0.0.0.0/0'.
    #dns_server = ''
    
    device_model = data_hw_info['Cisco-IOS-XE-device-hardware-oper:device-inventory'][0]['hw-description']
    serial_number = data_hw_info['Cisco-IOS-XE-device-hardware-oper:device-inventory'][0]['serial-number']
    
    host_id = len(hosts)
    router_id = ''
    used_macs = []
    used_ips = []

    ### Create host record for router.
    ip_list = []
    mac_list = []
    for intf in data_intf['ietf-interfaces:interfaces-state']['interface']:
        admin_status = intf['admin-status'] # If admin_status == 'up' -> turn link green? if down -> turn link red. or is this intf['oper-status']?
        link_speed = intf['speed'] # In bits/sec.
        mac_addr = intf['phys-address']
        portname = intf['name']

        if mac_addr not in mac_list:
            mac_list.append(mac_addr)
            used_macs.append(mac_addr)

    for arp_record in arp_table:
        if arp_record['hardware'] in mac_list:
            ip_list.append(arp_record['address'])
            used_ips.append(arp_record['address'])
    
    hostname = instance
    router_host = dict(host_id = host_id, MAC = mac_list, IPv4 = ip_list, port = portname, hostname = hostname, gateway = gw, dns_server = dns_server, serial_number = serial_number, device_model = device_model, router_ip = instance)
    hosts.append(router_host)
    host_id += 1

    ## Create host records for attached devices.
    # Parse ARP table.
    for arp_record in arp_table:
        ip_list = []
        mac_list = []

        ip_addr = arp_record['address']
        mac_addr = arp_record['hardware']
        portname = arp_record['interface']
        hostname = ip_addr

        if (ip_addr not in used_ips) and (mac_addr not in used_macs) and (mac_addr != '00:00:00:00:00:00'):

            mac_list.append(mac_addr)
            ip_list.append(ip_addr)
            used_macs.append(mac_addr)
            used_ips.append(ip_addr)
            host = dict(host_id = host_id, MAC = mac_list, IPv4 = ip_list, port = portname, hostname = hostname, gateway = "", dns_server = "", serial_number = "", device_model = "", router_ip = instance)
            hosts.append(host)
            host_id += 1

    return hosts 



### TODO: when adding second router in array -> put its hosts on the already existing node, except the ones that already exist (if ...) -> turn icon into router icon. -> add edges to second router node.






# GENERATE GRAPH.
def add_router_node(hosts, router_id):
    print(f'add_router_node(hosts, {router_id})')
    # Get list of all IP addresses already in graph.
    used_ips =[]
    if (router_id != 0):
        for host in hosts:
            for ip_address in host['IPv4']:
                used_ips.append(ip_address)

    for host in hosts:
        for ip_address in host['IPv4']:
            if ip_address in used_ips:
                used = True
            else:
                used = False
        
        if (router_id != host["host_id"]) and used:    
            print('Nodes')
            print(f'{host["host_id"]} - {host["router_ip"]} - {host["IPv4"]} - {host["port"]}')
            print(list(topology_map.nodes))

            topology_map.remove_node(host["host_id"])
            topology_map.add_node(router_id, label=host["hostname"], color='green', title=f'ID: {host["host_id"]}\nHostname: {host["hostname"]}\nIPv4: {host["IPv4"]}\nMAC: {host["MAC"]}\nSerial number: {host["serial_number"]}\nDevice model: {host["device_model"]}', shape='image', image='icons/router.png')

        # If it's a router and not yet used.
        if (router_id == host["host_id"]) and not used: ### CHECK IF ROUTER IS ALREADY IN GRAPH SOMEHOW.
            topology_map.add_node(router_id, label=host["hostname"], color='green', title=f'ID: {host["host_id"]}\nHostname: {host["hostname"]}\nIPv4: {host["IPv4"]}\nMAC: {host["MAC"]}\nSerial number: {host["serial_number"]}\nDevice model: {host["device_model"]}', shape='image', image='icons/router.png')
        
        


def add_nodes(hosts, router_id):
    ip_list = []
    for host in hosts:
        if host['router_id'] == router_id:
            create = True
            for ip in host['IPv4']:
                if ip not in ip_list:
                    ip_list.append(ip)
                else:
                    create = False

            if (create):
                topology_map.add_node(host["host_id"], label=f'{host["hostname"]}', title=f'ID: {host["host_id"]}\nHostname: {host["hostname"]}\nIPv4: {host["IPv4"]}\nMAC: {host["MAC"]}\nSerial number: {host["serial_number"]}\nDevice model: {host["device_model"]}')

def add_edges(hosts, router_id): #router_ip = instance
    # If connected_device == router_ip -> don't make edge (to prevent loopback connections)
    # Check how many devices are connected per router port.
    devices_per_port=dict()

    for host in hosts:
        port=host["port"]
        if (devices_per_port.get(port)):
            devices_per_port[port] += 1
        else:
            devices_per_port[port] = 1

    # Add a SWITCH, if 1+ connections per router port.
    for port in devices_per_port:
        if (devices_per_port[port] > 1) and (port != ''):
            switch_name=f'SWITCH_{router_id}_{port}' # Must be unique.
            print(f'Switch name: {switch_name}')
            topology_map.add_node(switch_name, color='red', title=switch_name, shape='image', image='icons/switch.png', label=' ')
            topology_map.add_edge(switch_name, router_id, label=f'{port}')

    # Create edges.
    for host in hosts:
        attached_device_id = host["host_id"]
        port = host["port"]
        router_id = host["router_id"]

        if ((devices_per_port[port]) > 1 and router_id != attached_device_id):
            switch_name=f'SWITCH_{router_id}_{port}' # Must be unique.
            topology_map.add_edge(switch_name, attached_device_id)
        else:
            if (port != '' and router_id != attached_device_id):
                topology_map.add_edge(router_id, attached_device_id, label=f'{port}')

def get_router_hosts(router, hosts=[]):


    # ----- Get hosts with Cisco RESTCONF API.
    hosts = get_hosts_restconf(router, hosts)

    # ----- Get hosts with SNMP.
    #(hosts, router_id) = get_hosts_snmp(router)

    # ----- Get hosts from manual file.
    hosts = get_hosts_manual(router, hosts) # Now gets it from database.

    # Determine 'router_id'.
    for host in hosts:
        if (instance in host['IPv4']):
            router_id = host['host_id']

    for host in hosts:
        if (host['router_ip'] == instance):
            host['router_id'] = router_id

    hosts = add_host_details(hosts)


    # Also needed for webUI inventory.
    store_hosts(hosts)
    # To test if program works with reading from the db.
    #hosts = read_hosts(router_id)

    return hosts

def get_router_id(ip_address):
    # Get ID for an IP.
    for host in hosts:
        #print(host['IPv4'])
        if (ip_address in host["IPv4"]) and (host["router_ip"] in host["IPv4"]):
            return host["host_id"]

    return False

def get_obsolete_ids(ip_address, router_id):
    # Get ID for an IP.
    host_ids = []
    ip_addresses = []
    for host in hosts:
        #print(host['IPv4'])
        if (ip_address in host["IPv4"]):
            host_ids.append(host["host_id"])
            ip_addresses = ip_addresses + host["IPv4"]

    print(ip_addresses)
    for ip_address2 in ip_addresses:
        for host in hosts:
            if (ip_address2 in host["IPv4"]):
                host_ids.append(host["host_id"])

    obsolete_ids = []

    for item in host_ids:
        if (item != router_id) and (item not in obsolete_ids):
            obsolete_ids.append(item)

    return obsolete_ids # => These are the duplicate node IDs.














if __name__ == ("__main__"):
    routers = read_json_from_file(sys.argv[1])

    # Create individual maps.
    for router in routers:
        instance = router['router_ip']

        topology_map = nx.Graph()
        
        instance_string = instance.split('.')
        instance_string = '_'.join(instance_string)

        time_string = str(timestamp).split('.')
        time_string = '_'.join(time_string)

        base_path = f'{current_directory}/app/Includes/topology_mapper'
        #TOPOLOGY_PATH_SINGLE = f'{current_directory}/app/Includes/topology_maps/topology_{instance_string}.html'
        TOPOLOGY_PATH_SINGLE = f'{current_directory}/public/topology_{instance_string}_{time_string}.html'

        hosts = []     
        print(f'\n\nGetting hosts from instance: {instance}')
        hosts = get_router_hosts(router, hosts)

        print(f'Instance: {instance}') # DEBUG
        router_id = get_router_id(instance)
        print(f'Router ID: {router_id}') # DEBUG

        obsolete_ids = get_obsolete_ids(instance, router_id)

        print('Obsolete IDs:') # DEBUG
        for obid in obsolete_ids: # DEBUG
            print(obid) # DEBUG

        #### HERE remove records from hosts that have an id in the obsolete_ids list.

        # Add a node for the router. and remove old nodes gotten from other routers
        add_router_node(hosts, router_id)

        # Add a node for every host.
        add_nodes(hosts, router_id)

        ## Connect the hosts with the router. (Add connections)
        add_edges(hosts, router_id)

        #remove_obsolete_nodes(obsolete_ids)

        print('Host ID - Router IP - Device IPs')
        for host in hosts:
            print(f'{host["host_id"]} - {host["router_ip"]} - {host["IPv4"]} - {host["port"]}')

        #print(f'# of hosts: {topology_map.number_of_nodes()}')


        
        nx.draw(topology_map)
        matplotlib.pyplot.savefig(f'{current_directory}/public/topology.png', format="PNG")
        print('drawn graph')
        sys.exit()

        # Enable hierarchical mode.
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
        net.from_nx(topology_map)
        #net.show_buttons(filter_=["layout", "physics"])
        net.set_options(OPTIONS)
        net.show(TOPOLOGY_PATH_SINGLE)
        net = None
    # -------------------------------


    # End program, because combined maps don't work yet.
    sys.exit()

    # Create combined map.
    topology_map = nx.Graph()
    hosts = []

    for router in routers:   
        instance = router['router_ip']
        print(f'\n\nGetting hosts from instance: {instance}')
        hosts = get_router_hosts(router, hosts)

    i = 0
    for router in routers:   
        instance = router['router_ip']
        i += 1

        print(f'Instance: {instance}') # DEBUG
        router_id = get_router_id(instance)
        print(f'Router ID: {router_id}') # DEBUG

        obsolete_ids = get_obsolete_ids(instance, router_id)

        print('Obsolete IDs:') # DEBUG
        for obid in obsolete_ids: # DEBUG
            print(obid) # DEBUG

        #### HERE remove records from hosts that have an id in the obsolete_ids list.

        # Add a node for the router. and remove old nodes gotten from other routers
        add_router_node(hosts, router_id)

        # Add a node for every host.
        add_nodes(hosts, router_id)

        ## Connect the hosts with the router. (Add connections)
        add_edges(hosts, router_id)

        #remove_obsolete_nodes(obsolete_ids)

    print('Host ID - Router IP - Device IPs')
    for host in hosts:
        print(f'{host["host_id"]} - {host["router_ip"]} - {host["IPv4"]} - {host["port"]}')

    #print(f'# of hosts: {topology_map.number_of_nodes()}')


    # Enable hierarchical mode.
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
    net.from_nx(topology_map)
    #net.show_buttons(filter_=["layout", "physics"])
    net.set_options(OPTIONS)
    net.show(TOPOLOGY_PATH)
    # -------------------------------
