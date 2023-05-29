import requests
import sys
import urllib3

from pprint import pprint # DEBUG.


query = "/data/Cisco-IOS-XE-native:native/interface" # Get list with interfaces (Interface IP + Index + netmask)
query = "/data/ietf-interfaces:interfaces/interface=GigabitEthernet3/ietf-ip:ipv4" #/neighbor does not work ... -> if it did we could loop over interfaces from query1 and fill it in interface=GigabitEthernet{n}
query = "/data/ietf-interfaces:interfaces-state" # Get list with interfaces (Index, MAC, portname, status, speed) (NO IP...)
query = "/data/ietf-interfaces:interfaces" # (IP, portname, status)
query_native_ip = "/data/Cisco-IOS-XE-native:native/ip" # Show default gateway! -> for route to internet (add node for internet/WAN)
query_routing_table = "/data/ietf-routing:routing-state" # Shows default gateway! (Network IPs + Ports + portname)
query = "/data/Cisco-IOS-XE-matm-oper:matm-oper-data"
query = "/data/netconf-state/capabilities"  # Shows all the yang modules this device can use!!
query_arp_table = "/data/Cisco-IOS-XE-arp-oper:arp-data/arp-vrf" # ARP TABLE! (MAC + IP + Portname)
query_interface = "/data/ietf-interfaces:interfaces-state" # Get list with interfaces (Index, MAC, portname, status, speed) (NO IP...)
#query = "/data/ietf-hardware:hardware/component"
query = "/data//Cisco-IOS-XE-device-hardware-oper:device-hardware-data/device-hardware/device-inventory"


instance = '10.0.0.5'

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

pprint(response_json)