<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Auth;


@include(app_path() . '/Includes/logging.php');

class PlaybookController extends Controller
{
    public function run()
    {
        // Import playbook data.
        $playbooks_data_path = app_path() . '/Includes/playbooks.json';

        $file = fopen($playbooks_data_path,'r');
        $playbooks = "";
        if ($file != 'false') {
            while (!feof($file)) {
                $line = fgets($file);
                $playbooks = $playbooks . $line;
            }
        } else {
            echo "Playbooks data file could not be opened.";
        }

        fclose($file);

        // To parse json and avoid errors.
        if ($playbooks == 'false') { # On failure
            $playbooks = array();
            $warning_message = "Playbooks file gives an error while trying to read the file ... So the previous manually added playbooks will be lost.";
            echo $warning_message;
        } elseif ($playbooks == '') { # Is empty.
            $playbooks = array();
            $warning_message = "The playbooks file is empty.";
            echo $warning_message;
        } else { # On success
            $playbooks = json_decode($playbooks, true);
            $warning_message = "Successfully read the playbooks file ... will append the input.";
        }
        // ------------
        
        
        // Interpret playbook data.
        $user = Auth::user();
        $user = $user['name'] . ' (ID: ' . $user['id'] . ')';
        $base_path = app_path() . '/Includes/playbooks/';
        $filename = request('selected_playbook');
        $path = $base_path . $filename;
        $target = '';
        $action = "Run playbook (" . $filename . ")";
        $user = "";
        $status = "Unknown";
        $variable_string = '';
        
        // Format all the variables.
        $inputs = array();
        foreach ($playbooks as $playbook) {
            if ($filename == $playbook['path']) {
                $variables = explode(', ', $playbook['variables']);
                $variable_string = '';

                if ($playbook['variables'] != "") {
                    foreach ($variables as $variable) {					
                        $inputs[$variable] = request($variable);
                        $variable_string = $variable_string . ' -e ' . $variable . '=' . $inputs[$variable];
                        
                        if ($variable == "client_ip") {
                            $target = $inputs[$variable];
                        }
                    }
                }
            }
        }
        
        $command = 'ansible-playbook' . $variable_string . ' ' . $path;
        
        // Execute playbook command
        $result = Process::run($command);
        $output = $result->output();
                
        if ($result->successful()) {
            $status = "OK";
            $details = $result->output();

            // Log action.
            $details = '<div>';
            $details = $details . '<p><b>Action:</b> ' . $action . '</p>';
            $details = $details . '<p><b>Started by</b> ' . $user . '</p>';
            $details = $details . '<p><b>Device:</b> ' . $target . '</p>';
            $details = $details . '<p><b>Playbook path:</b> ' . $path . '</p>';
            $details = $details . '<p><b>Status:</b> ' . 'Playbook executed successfully.' . '</p>';
            $details = $details . '</div>';

            create_log_record($action, $target, $user, $status, $details);
            
            
            return redirect('/playbook_output')->with('playbook_success',$output);
            #return 'Playbook executed successfully';
        } else {
            $status = "Failed.";
            $details = $result->errorOutput();

            // Log action.
            $details = '<div>';
            $details = $details . '<p><b>Action:</b> ' . $action . '</p>';
            $details = $details . '<p><b>Started by</b> ' . $user . '</p>';
            $details = $details . '<p><b>Device:</b> ' . $target . '</p>';
            $details = $details . '<p><b>Playbook path:</b> ' . $path . '</p>';
            $details = $details . '<p><b>Status:</b> ' . 'Playbook execution failed.' . '</p>';
            $details = $details . '</div>';

            create_log_record($action, $target, $user, $status, $details);
            
            return redirect('/playbook_output')->with('playbook_failure',$output);
            #return 'Playbook execution failed: ' . $result->errorOutput();
        }
    }
}

?>
