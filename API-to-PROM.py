import requests
import time
import hashlib
import json
from prometheus_client import start_http_server, Gauge

serial_number_metric = Gauge('device_serial_number', 'serial_number', ['device', 'serial_number'])
device_model_metric = Gauge('device_model', 'device_model', ['device', 'device_model'])

dns_server_metric = Gauge('device_dns_server', 'dns_server', ['device', 'dns_server'])
gw_metric = Gauge('device_gw', 'gw', ['device', 'gw'])

arp_table_metric = Gauge('device_arp_table', 'arp_table', ['device', 'arp_table'])
arp_table_data_metric = Gauge('device_arp_table_data', 'arp_table_data', ['device', 'host_id',  'mac', 'ipv4', 'port', 'hostname'])

intf_table_metric = Gauge('device_intf_table', 'intf_table', ['device', 'intf_table'])
intf_table_data_metric = Gauge('device_intf_table_data', 'intf_table_data', ['device', 'status', 'speed', 'mac', 'portname'])

routing_table_metric = Gauge('device_routing_table', 'routing_table', ['device', 'routing_table'])



def update_metrics():
    devices = ['10.0.0.5']  # Replace with the IP address of the device you want to monitor
    username = 'bram'  # Replace with your Cisco API username
    password = 'cisco'  # Replace with your Cisco API password
    headers = {'Accept': 'application/yang-data+json', 'Content-Type': 'application/yang-data+json'}   
    for device in devices:
        url = f'https://{device}/restconf/data/Cisco-IOS-XE-device-hardware-oper:device-hardware-data/device-hardware/device-inventory'
        url4 = f'https://{device}/restconf/data/ietf-interfaces:interfaces-state'
        url3 = f'https://{device}/restconf/data/Cisco-IOS-XE-arp-oper:arp-data/arp-vrf'
        url5 =  f'https://{device}/restconf/data/ietf-routing:routing-state'
        url2 = f'https://{device}/restconf/data/Cisco-IOS-XE-native:native/ip'


        response = requests.get(url, auth=(username, password), headers=headers, verify=False).json
        response2 = requests.get(url, auth=(username, password), headers=headers, verify=False)
        response3 = requests.get(url2, auth=(username, password), headers=headers, verify=False).json
        response4 = requests.get(url2, auth=(username, password), headers=headers, verify=False)
        response5 = requests.get(url3, auth=(username, password), headers=headers, verify=False).json
        response6 = requests.get(url3, auth=(username, password), headers=headers, verify=False)
        response7 = requests.get(url4, auth=(username, password), headers=headers, verify=False).json
        response8 = requests.get(url4, auth=(username, password), headers=headers, verify=False)
        response9 = requests.get(url5, auth=(username, password), headers=headers, verify=False).json
        response10 = requests.get(url5, auth=(username, password), headers=headers, verify=False)


        host_id = 0
        hosts = []
        router_id = ''
        used_macs = []
        used_ips = []




        if response2.status_code == 200:
            serial_number = response['Cisco-IOS-XE-device-hardware-oper:device-inventory'][0]['serial-number']
            serial_number_metric.labels(device=device, serial_number=serial_number).set(1)
            device_model = response['Cisco-IOS-XE-device-hardware-oper:device-inventory'][0]['hw-description']
            device_model_metric.labels(device=device, device_model=device_model).set(1)
        else:
            print(f'Error {response2.status_code} getting device hardware data for {device}')


        if response4.status_code == 200:
            dns_server = response3['Cisco-IOS-XE-native:ip']['name-server']['no-vrf']
            dns_server_metric.labels(device=device, dns_server=dns_server).set(1)
            gw = response3['Cisco-IOS-XE-native:ip']['route']['ip-route-interface-forwarding-list'][0]['fwd-list'][0]['fwd']
            gw_metric.labels(device=device, gw=gw).set(1)
        else:
            print(f'Error {response2.status_code} getting device hardware data for {device}')


        if response6.status_code == 200:
            arp_table = response5['Cisco-IOS-XE-arp-oper:arp-vrf'][0]['arp-entry']
            arp_table_metric.labels(device=device, arp_table=arp_table).set(1)

        for arp_record in arp_table:
            ip_list = []
            mac_list = []
            
            print('\n\n')
            print(f"{arp_record['address']}\n{arp_record['hardware']}\n{arp_record['interface']}\n")
            print('\n\n')

            ip_addr = arp_record['address']
            mac_addr = arp_record['hardware']
            portname = arp_record['interface']
            hostname = ip_addr

            if (ip_addr not in used_ips) and (mac_addr not in used_macs):

                mac_list.append(mac_addr)
                ip_list.append(ip_addr)
                used_macs.append(mac_addr)
                used_ips.append(ip_addr)
                host = dict(host_id = host_id, MAC = mac_list, IPv4 = ip_list, port = portname, hostname = hostname, gateway = "", dns_server = "", serial_number = "", device_model = "")
                hosts.append(host)
                host_id += 1
                print(host)
                arp_table_data_metric.labels(device=device, host_id=host_id, mac=mac_addr, ipv4=ip_addr, port=portname, hostname=hostname ).set(1)


        else:
            print(f'Error {response2.status_code} getting device hardware data for {device}')

        if response8.status_code == 200:
            intf_table = response7['ietf-interfaces:interfaces-state']['interface']
            intf_table_metric.labels(device=device, intf_table=intf_table).set(1)

            for intf in intf_table :
                print(intf)
                print('\n')
                admin_status = intf['admin-status'] # If admin_status == 'up' -> turn link green? if down -> turn link red. or is this intf['oper-status']?
                link_speed = intf['speed'] # In bits/sec.
                mac_addr = intf['phys-address']
                portname = intf['name']
                intf_table_data_metric.labels(device=device, status=admin_status, speed=link_speed, mac=mac_addr, portname=portname).set(1)



        else:
            print(f'Error {response2.status_code} getting device hardware data for {device}')         


        if response10.status_code == 200:
            routing_table = response9['ietf-routing:routing-state']['routing-instance'][0]['ribs']['rib'][0]['routes']['route']
            routing_table_metric.labels(device=device, routing_table=routing_table).set(1)
        else:
            print(f'Error {response2.status_code} getting device hardware data for {device}')


if __name__ == '__main__':
    # Start the Prometheus server on port 8000
    start_http_server(8000)
    while True:
        # Update the metrics every 30 seconds
        update_metrics()
        time.sleep(30)

