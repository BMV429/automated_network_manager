<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Auth;

@include(app_path() . '/Includes/logging.php');

class PingController extends Controller
{
    public function run() {
      $ip_address = request('device_ip');

      if (filter_var($ip_address, FILTER_VALIDATE_IP) === false) {
        $failure_msg = "<b>" . $ip_address . "</b> is not a valid IP address";
        return redirect()->back()->with('failure', $failure_msg);
      }
      
      $command = 'ping -c 1 ' . $ip_address . ' > /dev/null && echo "1" || echo "0"';
      $result = Process::run($command);        
      
      if ($result->output()==1) {
        $user = Auth::user();
        $action = "Ping device.";
        $target = $ip_address;
        $username = $user['name'] . ' (ID: ' . $user['id'] . ')';
        $status = $ip_address . " is not available.";

        $details = '<div>';
        $details = $details . '<p><b>Action:</b> ' . $action . ' <i>' . $target .  '</i>)</p>';
        $details = $details . '<p><b>Started by</b> ' . $username . '</p>';
        $details = $details . '<p><b>Status:</b> ' . 'Pings returned. </div> </p>';
        $details = $details . '</div>';

        create_log_record($action, $target, $username, $status, $details);
          
        #return view('/add_device')->with('message', 'The device is responding to the ping.');
        return redirect()->back()->with('failure', '<b>' . $ip_address . '</b> is in use. (responds to pings)');
      }
      else {
        $user = Auth::user();
        $action = "Ping device.";
        $target = $ip_address;
        $username = $user['name'] . ' (ID: ' . $user['id'] . ')';
        $status = "Pings not returned.";

        $details = '<div>';
        $details = $details . '<p><b>Action:</b> ' . $action . ' (<i>' . $target .  '</i>)</p>';
        $details = $details . '<p><b>Started by</b> ' . $username . '</p>';
        $details = $details . '<p><b>Status:</b> ' . 'Pings not returned. (' . $target . ')</p>';
        $details = $details . '</div>';
        
        create_log_record($action, $target, $username, $status, $details);
          
        #return view('/add_device')->with('message', 'The device probably does not exist.');
        return redirect()->back()->with('success', '<b>' . $ip_address . '</b> might be available. (does not respond to pings)');
      }
        
      return $result->output();
    }
}
?>
