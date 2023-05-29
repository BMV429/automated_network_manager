<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Process;

class StoreDeviceController extends Controller
{
    public function run() {
      // Initiate variables.
      $manual_hosts_path = app_path() . '/Includes/manual_hosts.json';
      $entry_id = 0; // increase entry id every time. (length of list?)

      // Get list of already added hosts (in manual_hosts.json).
      $file = fopen($manual_hosts_path,'r');
      $hosts_file = "";

      // -- Read file.
      while (!feof($file)) {
        $line = fgets($file);
        $hosts_file = $hosts_file . $line;
      }
      fclose($file);

      // -- Check contents.
      if ($hosts_file == 'false') { # If the file could not be read.
        $hosts = array();
        $warning_message = "Hosts file gives an error while trying to read the file ... So the previous manually added hosts will be lost.";
        echo $warning_message;
      } elseif ($hosts_file == '') { # When file read is empty.
        $hosts = array();
        $warning_message = "The hosts file is empty.";
        echo $warning_message;
      } else { # When file read is successful.
        $hosts = json_decode($hosts_file, true);
        $warning_message = "Successfully read the hosts file ... will append the input.";
      }
      
      $device_ip = request('device_ip');
      $device_mac = request('device_mac');
      $device_os = request('device_os');
      $device_model = request('device_model');
      $device_serial_no = request('device_serial_no');
      $device_username = request('device_username');
      $device_password = request('device_password');
      $connected_device_ip = request('connected_device_ip');
      $connected_device_port = request('connected_device_port');

      // Put all form inputs into an array (host).
      $host = array('entry_id' => $entry_id, 'IPv4' => $device_ip, 'MAC' => $device_mac, 'OS' => $device_os, 'model' => $device_model, 'sn' => $device_serial_no, 'device_username' => $device_username, 'device_password' => $device_password, 'connected_device_ip' => $connected_device_ip, 'connected_device_port' => $connected_device_port);

      // Open file.
      $hosts_file_pointer = fopen($manual_hosts_path, 'w') or die('Could not open the hosts file for writing.');

      // Add hosts array to file.
      array_push($hosts, $host);
      $warning_msg = "Successfully added device.";
      
      // Close the file.
      fwrite($hosts_file_pointer, json_encode($hosts));
      fclose($hosts_file_pointer);
      
      return view('/update_topology');    
    }
}
?>
