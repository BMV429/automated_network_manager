<?php

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);
echo ini_get('disable_functions');


#class Config {
	#const SQLITE_DB_PATH = base_path() . '/database/anmt.db';
	#const SQLITE_DB_PATH = '/var/www/html/database/anmt.db';
#}

class SQLiteConnection {
	private $pdo;

	public function connect() {
		if ($this->pdo == null) {
			#$this->pdo = new PDO("sqlite:" . Config::SQLITE_DB_PATH);
			$this->pdo = new PDO('sqlite:' . base_path() . '/database/anmt.db');
		}

		return $this->pdo;
	}
}

class SQLiteDB {
	private $pdo;

	public function __construct($pdo) {


		$this->pdo = $pdo;

		$this->create_tables();
	}

	public function create_tables() {
		$query = "CREATE TABLE IF NOT EXISTS 
					logs(
						log_id INTEGER PRIMARY KEY AUTOINCREMENT,
						timestamp INTEGER,
						action TEXT,
						target TEXT,
						user TEXT,
						status TEXT,
						details TEXT
					);";

		$this->pdo->exec($query);
	}

	public function insert_log($action, $target, $user, $status, $details) {

		$date = new DateTimeImmutable();
		$timestamp = $date->getTimestamp();

		$query = "INSERT INTO logs (timestamp, action, target, user, status, details) VALUES (:timestamp, :action, :target, :user, :status, :details);";
		$stmt = $this->pdo->prepare($query);

		$stmt->bindValue(':timestamp', $timestamp);
		$stmt->bindValue(':action', $action);
		$stmt->bindValue(':target', $target);
		$stmt->bindValue(':user', $user);
		$stmt->bindValue(':status', $status);
		$stmt->bindValue(':details', $details);

		$stmt->execute();

		return $this->pdo->lastInsertId();
	}
}

function get_device_list_short() {
	// Initiate variables.
	// WIP - Get devices from vis.db
	$device_table = "";
	try {
		$dbp = new PDO('sqlite:' . base_path() . '/database/anmt.db');
		$dbp->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

		$hosts = $dbp->query('SELECT * FROM hosts WHERE timestamp = (SELECT MAX(timestamp) FROM hosts);');

		# --- Populate device table.
		$device_table = "";
		$device_table = $device_table . "<table class='table'>";
		$device_table = $device_table . "<tr><th scope='col'>ID</th><th scope='col'>IP</th><th scope='col'>MAC</th></tr>";


		$router_hosts = array();

		foreach ($hosts as $host) { // adding this foreach gives the next one errors for some reason ...
			// Get list of host's IPs.
			$ip_array = explode('\', \'', trim($host["ip_list"], '][\''));

			// Push every router's IP to a list.
			if (in_array($host['router_id'], $ip_array)) {
				foreach ($ip_array as $ip_addr) {
					array_push($router_hosts, $ip_addr);
				}
			}
		}
		
		#var_dump($router_hosts);
		$hosts = $dbp->query('SELECT * FROM hosts WHERE timestamp = (SELECT MAX(timestamp) FROM hosts);');

			
		$i = 0;
		foreach ($hosts as $host) {
			
			// Get list of host's IPs.
			$ip_array = explode('\', \'', trim($host["ip_list"], '][\''));

			// Check xxx with router IPs. To remove duplicates.
			$visible_field = True;
			foreach ($ip_array as $ip_addr) {
				if (in_array($ip_addr, $router_hosts) && !in_array($host['router_id'], $ip_array)) { 
					$visible_field = False;
				}
			}

			
			if ($visible_field) {			
				$device_table = $device_table . "<tr class=" . (in_array($host['router_id'], explode('\', \'', trim($host["ip_list"], '][\''))) ? 'bg-light' : '') . ">";
				$device_table = $device_table . "<td scope='row'>" . $i . "</td>";
				$device_table = $device_table . "<td>" . implode('<br>', explode('\', \'', trim($host["ip_list"], '][\''))) . "</td>";
				$device_table = $device_table . "<td>" . implode('<br>', explode('\', \'', trim($host["mac_list"], '][\''))) . "</td>";
				//$device_table = $device_table . "<td><a href='/remove_device/" . $host['host_id'] . "'><button class='btn btn-danger'>x" . "" . "</button></a>";
				//$device_table = $device_table . "<a href="/remove_device/"><button class='btn btn-danger'>x" . "" . "</button></a></td>";
				$device_table = $device_table . "</tr>";
				$i = $i + 1;
			}
		}
		
		$device_table = $device_table . "</table>";
		# ------------

		// Close db connection.
		$dbp = null;
	}
	catch (PDOException $e) {
		echo $e->getMessage();
	}

	return $device_table;
	// --------



	// Get devices from manual_hosts.json
	$device_list_path = app_path() . '/Includes/manual_hosts.json';
	
	# --- Get already added hosts.
	$file = fopen($device_list_path,'r');
	$hosts_file = "";
	if ($file != 'false') {
		while (!feof($file)) {
			$line = fgets($file);
			$hosts_file = $hosts_file . $line;
		}
	} else {
		echo "Hosts file could not be opened.";
	}

	fclose($file);

	if ($hosts_file == 'false') { # On failure
		$hosts = array();
		$warning_message = "Hosts file gives an error while trying to read the file ... So the previous manually added hosts will be lost.";
		echo $warning_message;
	} elseif ($hosts_file == '') { # Is empty.
		$hosts = array();
		$warning_message = "The hosts file is empty.";
		echo $warning_message;
	} else { # On success
		$hosts = json_decode($hosts_file, true);
		$warning_message = "Successfully read the hosts file ... will append the input.";
	}
	# ------------

	# --- Populate device table.
	#$device_table = "";

	#$device_table = $device_table . "<table class='table'>";

	#$device_table = $device_table . "<tr><th scope='col'>ID</th><th scope='col'>IP</th><th scope='col'>OS</th><th scope='col'>MAC</th><th scope='col'>Model</th><th scope='col'>SN</th><th scope='col'>Connected to</th><th scope='col'>Port</th></tr>";
}

function get_device_list_long() {
	// Initiate variables.
	// WIP - Get devices from vis.db
	$device_table = "";
	try {
		$dbp = new PDO('sqlite:' . base_path() . '/database/anmt.db');
		$dbp->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

		$hosts = $dbp->query('SELECT * FROM hosts WHERE timestamp = (SELECT MAX(timestamp) FROM hosts);');

		# --- Populate device table.
		$device_table = "";
		$device_table = $device_table . "<table class='table'>";
		$device_table = $device_table . "<tr><th scope='col'>ID</th><th scope='col'>IP</th><th scope='col'>OS</th><th scope='col'>MAC</th><th scope='col'>Hostname</th><th scope='col'>Model</th><th scope='col'>SN</th><th scope='col'>Connected to</th><th scope='col'>Port</th><th></th></tr>";


		$router_hosts = array();

		

		foreach ($hosts as $host) { // adding this foreach gives the next one errors for some reason ...
			// Get list of host's IPs.
			$ip_array = explode('\', \'', trim($host["ip_list"], '][\''));

			// Push every router's IP to a list.
			if (in_array($host['router_id'], $ip_array)) {
				foreach ($ip_array as $ip_addr) {
					array_push($router_hosts, $ip_addr);
				}
			}
		}
		
		#var_dump($router_hosts);
		$hosts = $dbp->query('SELECT * FROM hosts WHERE timestamp = (SELECT MAX(timestamp) FROM hosts);');

			
		$i = 0;
		foreach ($hosts as $host) {
			
			// Get list of host's IPs.
			$ip_array = explode('\', \'', trim($host["ip_list"], '][\''));

			// Check xxx with router IPs. To remove duplicates.
			$visible_field = True;
			foreach ($ip_array as $ip_addr) {
				if (in_array($ip_addr, $router_hosts) && !in_array($host['router_id'], $ip_array)) { 
					$visible_field = False;
				}
			}

			
			if ($visible_field) {			
				$device_table = $device_table . "<tr class=" . (in_array($host['router_id'], explode('\', \'', trim($host["ip_list"], '][\''))) ? 'bg-light' : '') . ">";
				$device_table = $device_table . "<td scope='row'>" . $i . "</td>";
				$device_table = $device_table . "<td>" . implode('<br>', explode('\', \'', trim($host["ip_list"], '][\''))) . "</td>";
				$device_table = $device_table . "<td>" . (!empty($host["operating_system"]) ? $host["operating_system"] : '-') . "</td>";
				$device_table = $device_table . "<td>" . implode('<br>', explode('\', \'', trim($host["mac_list"], '][\''))) . "</td>";
				$device_table = $device_table . "<td>" . (in_array($host['hostname'], explode('\', \'', trim($host["ip_list"], '][\''))) ? '-' : $host["hostname"]) . "</td>";
				$device_table = $device_table . "<td>" . (!empty($host["device_model"]) ? $host["device_model"] : '-') . "</td>";
				$device_table = $device_table . "<td>" . (!empty($host["serial_number"]) ? $host["serial_number"] : '-') . "</td>";
				$device_table = $device_table . "<td>" . (in_array($host['router_id'], explode('\', \'', trim($host["ip_list"], '][\''))) ? '-' : $host['router_id']) . "</td>";
				$device_table = $device_table . "<td>" . (!empty($host["port"]) ? $host["port"] : '-') . "</td>";
				$device_table = $device_table . "<td>" . '<a href="/delete_device/' . implode('_', explode('.', explode('\', \'', trim($host["ip_list"], '][\''))[0])) . '"><button type="button" class="btn-close" aria-label="Remove"></button></a>' . "</td>";

				//$device_table = $device_table . "<td><a href='/remove_device/" . $host['host_id'] . "'><button class='btn btn-danger'>x" . "" . "</button></a>";
				//$device_table = $device_table . "<a href="/remove_device/"><button class='btn btn-danger'>x" . "" . "</button></a></td>";
				$device_table = $device_table . "</tr>";
				$i = $i + 1;
			}
		}
		
		$device_table = $device_table . "</table>";
		# ------------

		// Close db connection.
		$dbp = null;
	}
	catch (PDOException $e) {
		echo $e->getMessage();
	}

	return $device_table;
	// --------



	// Get devices from manual_hosts.json
	$device_list_path = app_path() . '/Includes/manual_hosts.json';
	
	# --- Get already added hosts.
	$file = fopen($device_list_path,'r');
	$hosts_file = "";
	if ($file != 'false') {
		while (!feof($file)) {
			$line = fgets($file);
			$hosts_file = $hosts_file . $line;
		}
	} else {
		echo "Hosts file could not be opened.";
	}

	fclose($file);

	if ($hosts_file == 'false') { # On failure
		$hosts = array();
		$warning_message = "Hosts file gives an error while trying to read the file ... So the previous manually added hosts will be lost.";
		echo $warning_message;
	} elseif ($hosts_file == '') { # Is empty.
		$hosts = array();
		$warning_message = "The hosts file is empty.";
		echo $warning_message;
	} else { # On success
		$hosts = json_decode($hosts_file, true);
		$warning_message = "Successfully read the hosts file ... will append the input.";
	}
	# ------------

	# --- Populate device table.
	#$device_table = "";

	#$device_table = $device_table . "<table class='table'>";

	#$device_table = $device_table . "<tr><th scope='col'>ID</th><th scope='col'>IP</th><th scope='col'>OS</th><th scope='col'>MAC</th><th scope='col'>Model</th><th scope='col'>SN</th><th scope='col'>Connected to</th><th scope='col'>Port</th></tr>";


}

function get_playbook_details($playbooks) {
    $base_path = app_path() . '/Includes/playbooks/';
	$filename = $_POST["selected_playbook"];
    $path = $base_path . $filename;
	$variable_options = '';

	$variable_options = $variable_options . '<p>Enter the following variables:</p>';
	$variable_options = $variable_options . '<input class="form-control" type="text" name="selected_playbook" value="' . $filename . '" aria-label="readonly input example" readonly>';

    foreach ($playbooks as $playbook) {
        if ($filename == $playbook['path']) {
            $variables = explode(', ', $playbook['variables']);

			if ($playbook['variables'] != "") {
                foreach ($variables as $variable) {					
                    $variable_options = $variable_options . '<div class="mb-3 col-md-4">' . "\n";
                    $variable_options = $variable_options . '<label for="' . $variable . '" class="form-label">' . $variable . '</label>' . "\n";
                    $variable_options = $variable_options . '<input type="text" class="form-control" id="' . $variable . '" name="' . $variable . '" placeholder="' . $variable . '">' . "\n";
                    #$variable_options = $variable_options . '<span class="invalid-feedback"></span>' . "\n";
                    $variable_options = $variable_options . '</div>' . "\n";
                }
            }

			$variable_options = $variable_options . '<div><input type="submit" name="submit" class="btn btn-danger" value="Execute"></div>';
        }
    }

	return $variable_options;
}

function use_playbook($playbooks) {
    $filename = $_POST['selected_playbook'];

    $base_path = app_path() . '/Includes/playbooks/';
    $path = $base_path . $filename;

    foreach ($playbooks as $playbook) {
        if ($filename == $playbook['path']) {
            $variables = explode(', ', $playbook['variables']);

            $variable_string = '';
			$target = "";

			foreach ($variables as $variable) {
				$variable_string = $variable_string . ' -e ' . $variable . '=' . $_POST[$variable];

				if ($variable == "client_ip") {
					$target = $_POST[$variable];
				}
			}


            $command = 'ansible-playbook' . $variable_string . ' ' . $path;

			// Putting action in logbook.
			$action = "Executed playbook (" . $filename . ")";
			$user = "Unknown";
			$status = "Unknown";
			// --------------------------

			
		
			// fix
			header('Location: /run_playbook/' . $command);
		
		
		
			$command_output = shell_exec($command);
			echo $command;
			echo $command_output;
			$command_output = "";

			// Check playbook output.
			$pattern = "/PLAY RECAP.*/";
			$debug = preg_replace($pattern, '', $command_output);
			$pattern = "/PLAY.*/";
			$debug = preg_replace($pattern, '', $debug);
			$pattern = "/TASK.*/";
			$debug = preg_replace($pattern, '', $debug);
			$details = $debug;
			#$debug = preg_match_all($pattern, $command_output);
			$pattern = "/fatal.*/";
			$debug = preg_replace($pattern, '', $debug);
			echo $debug;


			$pattern = "/failed=0/";
			$is_success = preg_match($pattern, $debug);


			if ($is_success == 0) {
				$status = "Failures occured.";
			}
			else {
				$status = "OK";
			}
			
			
			echo $is_success;
			echo $status;
			add_log_record($action, $target, $user, $status, $command_output); 		# add_log_record(status);
			# Put command output in log_details.
			# if fail=0 in command_output -> status = OK
        }
    }
}

function ping_device($ip_address) {
	$ip_pattern = "/\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}/";
	$is_valid_ip = preg_match($ip_pattern, $ip_address);

	echo "IS_VALID_IP";
	echo $is_valid_ip;
	$is_pingable = shell_exec(app_path() . '/Includes/ping_device.sh ' . $ip_address);
	echo $is_pingable;
	#return $is_pingable;
}

// -- Store form data into manual_hosts.json
function store_device_data() {
	// Initiate variables.
	$manual_hosts_path = app_path() . '/Includes/manual_hosts.json';
	$entry_id = 0; // increase entry id every time. (length of list?)

	$fields = array("device_ip", "device_mac", "device_os", "device_model", "device_serial_no", "device_username", "device_password", "connected_device_ip", "connected_device_port");
	$errors = array();
	$inputs = array();

	// Error messages. (perhaps store these in a seperate file later)

	// Check every 'needed' field to see if they are empty.
	foreach ($fields as $field) {
		if (!empty(trim($_POST[$field]))) {
			echo $field . " is not empty.";
			echo $_POST[$field];
			#$inputs[$field] = filter_input(INPUT_POST, $_POST[$field], FILTER_SANITIZE_STRING);
			$inputs[$field] = $_POST[$field];
			echo "   ";
			echo $_POST[$field];
			$errors[$field] = "";
		}
		else {
			echo $field . " is empty.";
			$inputs[$field] = "";
			$errors[$field] = "Please fill this field in.";
		}
	}

	// [Check for formatting errors here.]


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

	$fields = array("device_ip", "device_mac", "device_os", "device_model", "device_serial_no", "device_username", "device_password", "connected_device_ip", "connected_device_port");

	$device_ip = $inputs["device_ip"];
	$device_mac = $inputs["device_mac"];
	$device_os = $inputs["device_os"];
	$device_model = $inputs["device_model"];
	$device_serial_no = $inputs["device_serial_no"];
	$device_username = $inputs["device_username"];
	$device_password = $inputs["device_password"];
	$connected_device_ip = $inputs["connected_device_ip"];
	$connected_device_port = $inputs["connected_device_port"];

	// Put all form inputs into an array (host).
	$host = array('entry_id' => $entry_id, 'IPv4' => $device_ip, 'MAC' => $device_mac, 'OS' => $device_os, 'model' => $device_model, 'sn' => $device_serial_no, 'device_username' => $device_username, 'device_password' => $device_password, 'connected_device_ip' => $connected_device_ip, 'connected_device_port' => $connected_device_port);


	// Open file.
	$hosts_file_pointer = fopen($manual_hosts_path, 'w') or die('Could not open the hosts file for writing.');

	// Check for existence of devices.
	// -- Check ansible hosts file.
	$is_found = shell_exec('python3 /home/sb/automated_network_manager/topology_mapper/check_device_existence.py ' . $inputs["device_ip"]);
	// -- Check by pinging device.
	#$is_pingable = shell_exec('/home/sb/automated_network_manager/topology_mapper/ping_device.sh ' . $inputs["device_ip"]);
	// ADD: later only ping when is_found returns 0.

	echo $inputs["device_ip"];
	echo $is_found;
	$is_pingable = 0;
	if ($is_pingable == 1 && $is_found == 0) {
		echo "Adding device failed. The device already exists (ping).";
		$warning_msg = "Adding device failed. We pinged the device and got a response.";
	}
	elseif ($is_pingable == 0 && $is_found == 1) {
		echo "Adding device failed. The device already exists (found).";
		$warning_msg = "Adding device failed. This device already exists in our system.";
	}
	elseif ($is_pingable == 1 && $is_found == 1) {
		echo "Adding device failed. The device already exists (found + ping).";
		$warning_msg = "Adding device failed. This ping already exists in our system and it returned a ping.";
	}
	else {
		echo "Adding device success.";
		// Add hosts array to file.
		array_push($hosts, $host);
		$warning_msg = "Successfully added device.";
	}
	# ADD: 2 buttons -> PING, Just add.

	// Close the file.
	fwrite($hosts_file_pointer, json_encode($hosts));
	fclose($hosts_file_pointer);

	// Update topology on submit.
	$command_update_topology = "python3 /home/sb/automated_network_manager/topology_mapper/mapper_main.py 10.0.0.5 bram cisco"; # ADD: Find a way to not hardcode the password.
	$shell_output = shell_exec($command_update_topology);

	// Update files on submit.
	$command_update_hosts = "python3 /home/sb/automated_network_manager/network_scan/get_snmp_host.py";
	$shell_output = shell_exec($command_update_hosts);

	return $entry_id;		
}

function add_log_record($action, $target, $user, $status, $details) {
	// WIP - Get devices from vis.db
	try {
		// Connect to DB.
		$pdo = new PDO('sqlite:' . base_path() . '/database/anmt.db');
		$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

		// Create tables if they do not exist.
		#$query = 
		#$pdo->exec($query);
		// --------

		#$pdo = (new SQLiteConnection())->connect();
		$dbb = new SQLiteDB($pdo);

		$dbb->create_tables();

		$log_id = $dbb->insert_log($action, $target, $user, $status, $details);
	
		// Close db connection.
		$pdo = null;
	}
	catch (PDOException $e) {
		echo $e->getMessage();
	}
}

function get_logs() {
	try {
		$dbp = new PDO('sqlite:' . base_path() . '/database/anmt.db');
		$dbp->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

		$log_records = $dbp->query('SELECT * FROM logs ORDER BY log_id DESC LIMIT 200;');

		$logs_table = "";
		$logs_table = $logs_table . "<table class='table'>";
		$logs_table = $logs_table . "<tr><th scope='col'>ID</th><th scope='col'>timestamp</th><th scope='col'>action</th><th scope='col'>target</th><th scope='col'>user</th><th scope='col'>status</th></tr>";

		foreach ($log_records as $log_record) {
			
			$modal_button = '<button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#log_details_id_' . $log_record['log_id'] . '">
				Details
			</button>';

			$modal_box = '<!-- Modal -->
			<div class="modal fade" id="log_details_id_' . $log_record['log_id'] . '" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="log_details_id_' . $log_record['log_id'] . '_label" aria-hidden="true">
				<div class="modal-dialog">
				<div class="modal-content">
					<div class="modal-header">
					<h5 class="modal-title" id="log_details_id_' . $log_record['log_id'] . '_label">Details for log #' . $log_record['log_id'] . '</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
					</div>
					<div class="modal-body">
					<p>' . (!empty($log_record["details"]) ? $log_record["details"] : 'No extra details.') . '</p>
					</div>
					<div class="modal-footer">
					<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
					</div>
				</div>
				</div>
			</div>';


			$logs_table = $logs_table . "<tr>";
			$logs_table = $logs_table . "<td scope='row'>" . $log_record['log_id'] . "</td>";
			$logs_table = $logs_table . "<td>" . date('m/d/Y H:i:s', $log_record["timestamp"]) . "</td>";
			$logs_table = $logs_table . "<td>" . (!empty($log_record["action"]) ? $log_record["action"] : '-') . "</td>";
			$logs_table = $logs_table . "<td>" . (!empty($log_record["target"]) ? $log_record["target"] : '-') . "</td>";
			$logs_table = $logs_table . "<td>" . (!empty($log_record["user"]) ? $log_record["user"] : '-') . "</td>";
			$logs_table = $logs_table . "<td>" . (!empty($log_record["status"]) ? $log_record["status"] : '-') . "</td>";
			$logs_table = $logs_table . "<td>" . $modal_button . "</td>";
			$logs_table = $logs_table . "</tr>";
			
			$logs_table = $logs_table . $modal_box;
		}

		$logs_table = $logs_table . "</table>";

		//$logs_table = $logs_table . "<div class='container-fluid log_details_container'> Log details </div>";


		// Close db connection.
		$dbp = null;

		return $logs_table;
	}
	catch (PDOException $e) {
		echo $e->getMessage();
	}
}



# -- Show playbooks
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

$playbook_options = '';
foreach ($playbooks as $playbook) {
    $playbook_options = $playbook_options . '<option value="' . $playbook['path'] . '">' . $playbook['name'] . '</option>';
}
?>
