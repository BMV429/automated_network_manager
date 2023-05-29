<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Crypt;


class update_topology_job implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
      $secrets_path = storage_path() . '/app/secr.txt';
      $program_path = app_path() . '/Includes/topology_mapper/mapper_main.py';
      $command = 'python3 ' . $program_path . ' ' . $secrets_path;
    }

    public function decrypt($encrypted_secret) {
      try {
        $plain_secret = Crypt::decryptString($encrypted_secret);
      } catch (DecryptException $e) {
        $error_msg = '';
      }
      return $plain_secret;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Execute python script.
        $secrets_path = storage_path() . '/app/secr.txt';
        $program_path = app_path() . '/Includes/topology_mapper/mapper_main.py';
        $command = 'python3 ' . $program_path . ' ' . $secrets_path;

        // Temporarily store decrypted credentials.
        $routers = DB::select('select * from routers');

        $decrypted_routers = array();
        foreach ($routers as $router) {
            $router_password = Crypt::decryptString($router->router_secret);
            
            $drouter = $array = [
            "router_id" => $router->router_id,
            "router_ip" => $router->router_ip,
            "router_username" => $router->router_username,
            "router_password" => $router_password
            ];

            array_push($decrypted_routers, $drouter);
        }

        Storage::disk('local')->put('secr.txt', 'hello');

        $result = Process::run($command);

        $command = 'python3 ' . $program_path . ' "' . $secrets_path . '"';

        $user = 'System (cronjob)';
        $action = "Updated topology map and inventory.";
        $target = "";
        $status = "OK";
        $details = $command; // Add all routers that got updated here.

        create_log_record($action, $target, $user, $status, $details);
    }
}
