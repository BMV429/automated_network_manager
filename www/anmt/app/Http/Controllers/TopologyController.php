<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

@include(app_path() . '/Includes/logging.php');

class TopologyController extends Controller
{
    public function delete_router($router_id) {
      $deleted = DB::delete('DELETE FROM routers WHERE router_id == ?', [$router_id]);
      $failure_msg = 'Deleted router ' . $router_id . '.';
      return redirect()->back()->with('failure', $failure_msg);
    }
    
    public function store_router() {
      $secret = $array = [
        "router_ip" => htmlspecialchars(request('router_ip')),
        "router_username" => htmlspecialchars(request('router_username')),
        "router_password" => htmlspecialchars(request('router_password'))
      ];

      if (filter_var($secret['router_ip'], FILTER_VALIDATE_IP) === false) {
        $failure_msg = "<b>" . $secret['router_ip'] . "</b> is not a valid IP address";
        return redirect()->back()->with('failure', $failure_msg);
      }

      // If router already in database.
      $routers = DB::select('select * from routers');
      foreach ($routers as $router) {
        if ($secret['router_ip'] == $router->router_ip) {
          $failure_msg = "<b>" . $secret['router_ip'] . "</b> has already been stored.";
          return redirect()->back()->with('failure', $failure_msg);
        }
      }
      



      // Securely store password.
      $encrypted_secret = $this->encrypt($secret['router_password']);
      DB::insert('insert into routers (router_ip, router_username, router_secret) values (?, ?, ?)', [$secret["router_ip"], $secret["router_username"], $encrypted_secret]);
      $success_msg = "Succesfully stored " . $secret['router_ip'];
      return redirect()->back()->with('success', $success_msg);
    }

    public function get_routers() {
      $routers = DB::select('select * from routers');
      return redirect()->back()->with('routers', $routers);
    }

    public function decrypt($encrypted_secret) {
      try {
        $plain_secret = Crypt::decryptString($encrypted_secret);
      } catch (DecryptException $e) {
        $error_msg = '';
      }
      return $plain_secret;
    }

    public function encrypt($plain_secret) {
      $encrypted_secret = Crypt::encryptString($plain_secret);
      return $encrypted_secret;
    }

    public function update_topology() {
      // Execute python script.
      $secrets_path = storage_path() . '/app/secr.txt';
      $program_path = app_path() . '/Includes/topology_mapper/mapper_main.py';
      $command = 'python3 ' . $program_path . ' ' . $secrets_path;

      // Temporarily store decrypted credentials.
      $routers = DB::select('select * from routers');

      $decrypted_routers = array();
      $targets = array(); // LOGS.

      foreach ($routers as $router) {
        $router_password = $this->decrypt($router->router_secret);
        
        $drouter = $array = [
          "router_id" => $router->router_id,
          "router_ip" => $router->router_ip,
          "router_username" => $router->router_username,
          "router_password" => $router_password
        ];
        
        array_push($targets, $router->router_ip); // LOGS.
        array_push($decrypted_routers, $drouter);
      }

      Storage::disk('local')->put('secr.txt', json_encode($decrypted_routers));

      $result = Process::run($command);

      // Remove sensitive details.
      Storage::disk('local')->put('secr.txt', '');

      $command = 'python3 ' . $program_path . ' "' . $secrets_path . '"';

      $user = Auth::user();
      $user = $user['name'] . ' (ID: ' . $user['id'] . ')';
      $action = "Updated topology map and inventory.";
      $target = "";
      $status = "OK";

      $details = '<div>';
      $details = $details . '<p><b>Action:</b> ' . $action . '</p>';
      $details = $details . '<p><b>Started by</b> ' . $user . '</p>';
      $details = $details . '<p><b>Program path:</b> ' . $program_path . '</p>';
      $details = $details . '<p><b>Targets:</b> ' . implode(', ', $targets) . '</p>';
      $details = $details . '<p><b>Status:</b> ' . 'Executed successfully.' . '</p>';
      $details = $details . '</div>';

      create_log_record($action, $target, $user, $status, $details);

      echo $result->output();
      return redirect('/topology');
    }
}
?>
