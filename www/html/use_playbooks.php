<?php
session_start();

require_once '/var/www/anmt/html/config.php';

$page_title = 'Add device';
$current_page = strtolower($page_title);

# -- Show hosts.
$hosts = array('Windows', 'Linux', 'Cisco router');
$host_options = '';
foreach ($hosts as $i => $host) {
    $host_options = $host_options . '<option value="' . $i . '">' . $host . '</option>';
}

$variable_options = "";
$playbook_selection = "Select a playbook.";

$device_table = get_device_list_short();
#populate_devices_table();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
	switch($_POST['submit']) {
		case 'Get details': 
			$variable_options = get_playbook_details($playbooks);
		break;
		case 'Execute':
			use_playbook($playbooks);
		break;
	}
}
?>

<!DOCTYPE html>
<html>
	<head>
		<title>Automated Network Managing Tool | Use playbooks</title>
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

			<div class="title_wrapper">
				<h1>ANMT</h1>
				<p>Automated Network Managing Tool</p>
			</div>

			<div class="center_wrapper">

				<div id="playbooks_list_wrapper">
                    <form name="get_playbook_details_form" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post" class="row g-3">
                        <select class="form-select" name="selected_playbook">
                            <option selected><?php echo $playbook_selection; ?></option>
                            <?php echo $playbook_options; ?> <!-- Probably don't want to put real paths in your html ... -->
                        </select>
                        <!--<select class="form-select" name="selected_host">
                            <option selected>Select a host.</option>
                            <?php #echo $host_options; ?>
                        </select> -->
    					<input type="submit" name="submit" class="btn btn-primary" value="Get details">
                    </form>
                    <form name="use_playbooks_form" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post" class="row g-3">
                        <?php echo $variable_options; ?>
                    </form>
				</div>
				
				<div class="topology_map_wrapper">
                    <iframe id="topology_map"
                        src="current_topology.html"
                        name="Topology"
                        scrolling="no"
                        frameborder="0"
                        height="100%"
                        width="100%"
                    > </iframe>
                </div>

		</div>
	</div>

	<script src="/helper.js"></script>
	<!-- Bootstrap javascript -->
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js" integrity="sha384-w76AqPfDkMBDXo30jS1Sgez6pr3x5MlQ1ZAGC+nuZB+EYdgRZgiwxhTBTkF7CXvN" crossorigin="anonymous"></script>
	</body>
</html>
