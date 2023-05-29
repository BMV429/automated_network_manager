#!/bin/bash

###                                         ###
# Only execute after installing Prometheus.   #
# This script is used to 
###                                         ###


# Check if prometheus is installed.
if service --status-all | grep -Fq 'prometheus'; then    
    cd ˜

    if service --status-all | grep -Fq 'docker'; then

        # Download the snmp_exporter repository and pre-requisites.
        cd /etc/prometheus
        git clone https://github.com/prometheus/snmp_exporter
        sudo apt-get update && sudo apt-get install unzip build-essential libsnmp-dev
        cd /etc/prometheus/snmp_exporter/generator
        mkdir mibs

        # Download default MIBs.
        make mibs

        ## Get Linux MIBS.
        cp -r /usr/share/snmp/mibs/* ./mibs/

        # Get Cisco MIBS.
        cd /etc/prometheus/snmp_exporter/generator/mibs
        git clone https://github.com/cisco/cisco-mibs.git
        wget https://bestmonitoringtools.com/mibdb/mibs/RFC1213-MIB.mib
        cd /etc/prometheus/snmp_exporter/generator

        # Build the snmp-generator image.
        sudo docker build -t snmp-generator .

        # Get snmp_exporter binaries.
        cd /etc/prometheus/snmp_exporter
        wget https://github.com/prometheus/snmp_exporter/releases/download/v0.21.0/snmp_exporter-0.21.0.linux-amd64.tar.gz # Might need to be updated...
        tar -x -f /etc/prometheus/snmp_exporter/snmp_exporter-0.21.0.linux-amd64.tar.gz
        rm /etc/prometheus/snmp_exporter/snmp_exporter-0.21.0.linux-amd64.tar.gz
        
        # Copy generator.yml to the right location.
        cp /home/sb/automated_network_manager/generator.yml /etc/prometheus/snmp_exporter/generator/generator.yml
    else
        # Install Docker.
        curl -fsSL https://get.docker.com -o ˜/get-docker.sh && sudo sh ˜/get-docker.sh && echo "Docker successfully installed."
    fi
else
    echo "Please install Prometheus first."
    echo "<sudo apt-get install prometheus>"
    exit;
fi
