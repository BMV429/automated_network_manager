<?php 
echo "Automatic redirection failed. Please manually go back.";

$command_update_topology = "python3 /home/sb/automated_network_manager/topology_mapper/mapper_main.py 10.0.0.5 bram cisco"; // Find a way to not hardcode the password.
$shell_output = shell_exec($command_update_topology);
echo $shell_output;
header("Location: /");
#exit();
?>