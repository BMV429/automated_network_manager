<?php

namespace App\Http\Controllers;

@include(app_path() . '/Includes/logging.php');

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StoreDeviceController extends Controller
{
    public function delete_device($device_ip) {
      $device_ip = implode('.', explode('_', $device_ip));
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

      foreach ($hosts as $i => $host) {
        if ($device_ip == $host["IPv4"]) {
          unset($hosts[$i]);
        }
      }
      $hosts = array_values($hosts);

      $hosts_file_pointer = fopen($manual_hosts_path, 'w') or die('Could not open the hosts file for writing.');

      // Close the file.
      fwrite($hosts_file_pointer, json_encode($hosts));
      fclose($hosts_file_pointer);

      $user = Auth::user();
        
      // Log action.
      $action = "Removed device from inventory.";
      $target = $device_ip;
      $username = $user['name'] . ' (ID: ' . $user['id'] . ')';
      $status = "OK";
      
      $details = '<div>';
      $details = $details . '<p><b>Action:</b> ' . $action . '</p>';
      $details = $details . '<p><b>Started by</b> ' . $username . '</p>';
      $details = $details . '<p><b>Added device:</b> ' . $target . '</p>';
      $details = $details . '<p><b>Status:</b> ' . 'Device successfully removed.' . '</p>';
      $details = $details . '</div>';
      
      create_log_record($action, $target, $username, $status, $details);

      return view('/update_topology');   
    }



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
      
      // Inputs.
      $device_ip = htmlspecialchars(request('device_ip'));
      $device_mac = htmlspecialchars(request('device_mac'));
      $device_os = htmlspecialchars(request('device_os'));
      $device_model = htmlspecialchars(request('device_model'));
      $device_serial_no = htmlspecialchars(request('device_serial_no'));
      $device_username = htmlspecialchars(request('device_username'));
      $device_password = htmlspecialchars(request('device_password'));
      $connected_device_ip = htmlspecialchars(request('connected_device_ip'));
      $connected_device_port = htmlspecialchars(request('connected_device_port'));

      // Validate inputs.
      if (filter_var($device_ip, FILTER_VALIDATE_IP) === false) {
        echo "$device_ip is not a valid IP address";
        return 0;
      }

      if (filter_var($connected_device_ip, FILTER_VALIDATE_IP) === false) {
        echo "$connected_device_ip is not a valid IP address";
        return 0;
      }

      if (filter_var($device_mac, FILTER_VALIDATE_MAC) === false) {
        echo "$device_mac is not a valid MAC address";
        return 0;
      }
      
      # Get all hosts.
      #$hosts = DB::select('select host_id from hosts order by 1 desc');
      #$entry_id = (int)$hosts[0]->host_id + 1;
      
      // Store host in database.
      #DB::insert('INSERT INTO hosts (host_id, mac_list, ip_list, port, hostname, default_gateway, dns_server, serial_number, device_model, operating_system, router_id VALUES(? ,? ,? ,? ,? ,? ,? ,? ,? ,? ,?)', [(int)$entry_id, $device_mac, $device_ip, $connected_device_port, $device_ip, '', '', $device_serial_no, $device_model, $device_os, (int)$connected_device_ip]);


      $entry_id = sizeof($hosts);
      // Put all form inputs into an array (host).
      $host = array('entry_id' => $entry_id, 'IPv4' => $device_ip, 'MAC' => $device_mac, 'OS' => $device_os, 'model' => $device_model, 'sn' => $device_serial_no, 'device_username' => $device_username, 'device_password' => $device_password, 'connected_device_ip' => $connected_device_ip, 'connected_device_port' => $connected_device_port);

      // Open file.
      $hosts_file_pointer = fopen($manual_hosts_path, 'w') or die('Could not open the hosts file for writing.');

      // Add hosts array to file.
      array_push($hosts, $host);

      DB::insert('insert into manual_hosts (IPv4, MAC, OS, model, sn, device_username, device_password, connected_device_ip, connected_device_port) values (?, ?, ?, ?, ?, ?, ?, ?, ?)', [$device_ip, $device_mac, $device_os, $device_model, $device_serial_no, $device_username, $device_password, $connected_device_ip, $connected_device_port]);

      $warning_msg = "Successfully added device.";
      
      // Close the file.
      fwrite($hosts_file_pointer, json_encode($hosts));
      fclose($hosts_file_pointer);

      $user = Auth::user();
        
      // Log action.
      $action = "Manually add device.";
      $target = $device_ip;
      $username = $user['name'] . ' (ID: ' . $user['id'] . ')';
      $status = "OK";
      
      $details = '<div>';
      $details = $details . '<p><b>Action:</b> ' . $action . '</p>';
      $details = $details . '<p><b>Started by</b> ' . $username . '</p>';
      $details = $details . '<p><b>Added device:</b> ' . $target . '</p>';
      $details = $details . '<p><b>Status:</b> ' . 'Added new device successfully.' . '</p>';
      $details = $details . '</div>';
      
      create_log_record($action, $target, $username, $status, $details);
      
      return view('/update_topology');    
    }
}
?>
