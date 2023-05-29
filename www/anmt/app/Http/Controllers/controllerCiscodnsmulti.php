<?php

namespace App\Http\Controllers;

use Symfony\Component\Process\Process;

class controllerCiscodnsmulti extends Controller
{
    public function run()
    {
        // Get form data
        $dnsServerIp = request('dns_server_ip');
        $ansible_user = request('ansible_user');
        $ansible_password = request('ansible_password');
        $ansible_become_password = request('ansible_become_password');
        
        // Construct command to run playbook
        $playbookPath = '/var/www/anmt/html/anmt/app/Includes/playbooks/Ciscodnssingle.yml';
        $command = sprintf('ansible-playbook %s -e ansible_become_password=%s -e ansible_user=%s -e ansible_password=%s -e dns_server_ip=%s', $playbookPath, $ansible_become_password, $ansible_user, $ansible_password, $dnsServerIp );

        // Execute playbook command
        $process = new Process($command);
        $process->run();

        if ($process->isSuccessful()) {
            return 'Playbook executed successfully';
        } else {
            return 'Playbook execution failed: ' . $process->getErrorOutput();
        }
    }
}
