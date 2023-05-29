<?php
session_start();
// Insert default config.
require_once '/var/www/anmt/html/config.php';

$page_title = 'Add device';
$current_page = strtolower($page_title);

// Show inventory on page load.
$device_table = get_device_list_short();

// On form submit.
if ($_SERVER["REQUEST_METHOD"] == "POST") {
	switch($_POST['submit']) {
		case 'Add device': 
			$entry_id = store_device_data();

			$action = "Manually added device (device_id: " . $entry_id . ")";
			$target = "None";
			$user = "";
			$status = "OK";
			$details = "";

			add_log_record($action, $target, $user, $status, $details);
		break;
		case 'Ping device':
			$ip_addr = $_POST['device_ip'];
			ping_device($ip_addr);
		break;
	}
}
?>

<!DOCTYPE html>
<html>
	<head>
		<title>Automated Network Managing Tool | Add device</title>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		
		<link rel="stylesheet" href="reset.css">
		<!-- Bootstrap CSS -->
		<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-GLhlTQ8iRABdZLl6O3oVMWSktQOp6b7In1Zl3/Jr59b6EGGoI1aFkw7cmDA6j6gD" crossorigin="anonymous">
		<link rel="stylesheet" href="stylesheet.css">
	</head>
	<body>
		<div class="main_wrapper">
		
			<div class="navigation_bar">
				<img src="logo.png" alt="Company logo">
			</div>

			<div class="center_wrapper">
				<div id="new_device_form_wrapper">


				<form name="new_device_form" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post" class="row g-3 needs-validation" novalidate>
					<div class="col-md-4">
						<label for="device_ip" class="form-label">IP address</label>
						<input type="text" class="form-control" id="device_ip" name="device_ip" placeholder="e.g. 10.0.32.40" required>
						<div class="invalid-feedback">
						Please enter an IP address.
						</div>
					</div>
					<div class="col-md-4">
						<label for="device_mac" class="form-label">MAC address</label>
						<input type="text" class="form-control" id="device_mac" name="device_mac" placeholder="e.g. A0:B4:44:FF:C2:EE" required>
						<div class="invalid-feedback">
						Please enter a MAC address.
						</div>
					</div>
					<div class="col-md-3">
						<label for="device_serial_no" class="form-label">Serial number</label>
						<input type="text" class="form-control" id="device_serial_no" name="device_serial_no" placeholder="XLKS34K4" required>
						<div class="invalid-feedback">
						Please provide a valid serial number.
						</div>
					</div>
					<div class="col-md-5">
						<label for="device_os" class="form-label">Operating system</label>
						<input class="form-select" list="operating_systems" id="device_os" name="device_os" placeholder="Type to search..." required>
						<div class="invalid-feedback">
						Please select a valid operating system.
						</div>
					</div>
					<div class="col-md-6">
						<label for="device_model" class="form-label">Device model</label>
						<input type="text" class="form-control" id="device_model" name="device_model" placeholder="Model">
						<div class="invalid-feedback">
						Please provide a valid device model.
						</div>
					</div>
					<div class="col-md-5">
						<label for="device_username" class="form-label">Username</label>
						<input type="text" class="form-control" id="device_username" name="device_username" placeholder="Username" required>
						<div class="invalid-feedback">
						Please provide a valid username.
						</div>
					</div>
					<div class="col-md-6">
						<label for="device_password" class="form-label">Password</label>
						<input type="text" class="form-control" id="device_password" name="device_password" placeholder="Password" required>
						<div class="invalid-feedback">
						Please provide a valid password.
						</div>
					</div>
					<div class="col-md-6">
						<label for="connected_device_ip" class="form-label">Router / Switch IP</label>
						<input type="text" class="form-control" id="connected_device_ip" name="connected_device_ip" placeholder="10.0.0.5" required>
						<div class="invalid-feedback">
						Please provide a valid router ip.
						</div>
					</div>
					<div class="col-md-5">
						<label for="connected_device_port" class="form-label">Port</label>
						<input type="text" class="form-control" id="connected_device_port" name="connected_device_port" placeholder="GigabitEthernet3" required>
						<div class="invalid-feedback">
						Please provide a valid router port.
						</div>
					</div>
					<div class="col-md-11">
						<label for="device_notes" class="form-label">Notes</label>
						<textarea class="form-control" id="device_notes" name="device_notes" rows="3" placeholder="Things like physical location or remarks can be put here."></textarea>
					</div>
					<datalist id="operating_systems">
                            <option value="Cisco IOS">
                            <option value="Windows">
                            <option value="Linux">
                            <option value="FreeBSD">
                            <option value="Other">
                        </datalist>
					<div class="col-12">
						<input type="submit" name="submit" class="btn btn-primary" value="Add device">
						<input type="submit" name="submit" class="btn btn-secondary" value="Ping device">

					</div>
					</form>
				</div>

				<div class="device_table_wrapper">
					<?php echo $device_table; ?>
				</div>

		</div>
	</div>

	<script src="/helper.js"></script>
	<!-- Bootstrap javascript -->
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js" integrity="sha384-w76AqPfDkMBDXo30jS1Sgez6pr3x5MlQ1ZAGC+nuZB+EYdgRZgiwxhTBTkF7CXvN" crossorigin="anonymous"></script>
	</body>
</html>
