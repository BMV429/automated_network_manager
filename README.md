# Automated Network Manager

### Welkom op de repository van onze batchelorproef

Voor opzetten van deze tool moet u eerst zelf een ansible server installeren. Hierna moet u deze repository downlaoden. in de repository kunt u dan navigeren naar automated_network_manager/www/anmt/app/Includes/playbooks/ . Hier kunt u dan het volgende commando uitvoeren voor de tool te installeren.

ansible-playbook plabookinstallprome2.yml --ask-vault-pass

Hierna zou de tool moeten opgezet zijn in zou u de web UI moeten kunnen bekijken. 

De default credentials zijn `admin@example.com`/`anmtadmin`.


### Om de code te zien van alle playbooks. 

playbooks: [automated_network_manager/www/anmt/app/Includes/playbooks](https://github.com/BMV429/automated_network_manager/tree/main/www/anmt/app/Includes/playbooks)

### Om de code te zien van alle gebruikte templates.

templates: [automated_network_manager/templates](https://github.com/BMV429/automated_network_manager/tree/main/templates)

### Om de code te zien voor het maken van de hosts files.  

network_scan: [automated_network_manager/network_scan](https://github.com/BMV429/automated_network_manager/tree/main/network_scan)

### Voor de code te zien die de topologies kan tekenen.

topology tekenen: [automated_network_manager/www/anmt/app/Includes/topology_mapper/mapper_main.py](https://github.com/BMV429/automated_network_manager/blob/main/www/anmt/app/Includes/topology_mapper/mapper_main.py)

### Voor de code te zien in verband met de webUI. 

WebUI:[automated_network_manager/www/anmt/resources/views](https://github.com/BMV429/automated_network_manager/tree/main/www/anmt/resources/views)

### Voor de code te zien in verband met de Laravel controllers.

Laravel controllers: [automated_network_manager/www/anmt/app/Http/Controllers](https://github.com/BMV429/automated_network_manager/tree/main/www/anmt/app/Http/Controllers) 


