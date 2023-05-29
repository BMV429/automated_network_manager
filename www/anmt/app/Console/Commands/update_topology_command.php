<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Crypt;

@include(app_path() . '/Includes/logging.php');


class update_topology_command extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'topology:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command updates the network topology.';



    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();
    }

    

    /**
     * Execute the console command.
     */
    public function handle()
    {
       // Execute python script.
       $secrets_path = storage_path() . '/app/secr.txt';
       $program_path = app_path() . '/Includes/topology_mapper/mapper_main.py';
       $command = 'python3 ' . $program_path . ' ' . $secrets_path;

       // Temporarily store decrypted credentials.
       $routers = DB::select('select * from routers');

       $decrypted_routers = array();
       $targets = array(); // LOGS.
       foreach ($routers as $router) {
           $router_password = Crypt::decryptString($router->router_secret);
           
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

       $command = 'python3 ' . $program_path . ' "' . $secrets_path . '"';

       // LOGS
       $user = 'System (scheduled)';
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
    }
}
