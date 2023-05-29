<?php

namespace App\Http\Controllers;

use Symfony\Component\Process\Process;

class controllerCiscosnmpmulti extends Controller
{
    public function run()
    {
        // Get form data
        $user = request('user');
        $group = request('group');
        $authpass = request('auth_pass');
        $privpass = request('privpass');
        $ansible_ssh_user = request('ansible_ssh_user');
        $ansible_ssh_pass = request('ansible_ssh_pass');
        $ansible_become_password = request('ansible_become_password');
        
        // Construct command to run playbook
        $playbookPath = '/var/www/anmt/html/anmt/app/Includes/playbooks/Ciscodnssingle.yml';
        $command = sprintf('ansible-playbook %s -e ansible_become_password=%s -e ansible_ssh_user=%s -e ansible_ssh_pass=%s -e user=%s -e group=%s -e authpass=%s -e privpass=%s ', $playbookPath, $ansible_become_password, $ansible_ssh_user, $ansible_ssh_pass, $user, $group, $authpass, $privpass);

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
