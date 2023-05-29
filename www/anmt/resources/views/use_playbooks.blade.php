<?php

include(app_path().'/Includes/config.php');
$page_title = 'Run playbooks';
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
		case 'Test':
			use_playbook($playbooks);
		break;
	}
}
?>

<!DOCTYPE html>
<html>
	<head>
		<?php include(base_path() . '/resources/views/partials/header.blade.php'); ?>
	</head>
	<body>
		<div class="main_wrapper">
		
			@include( 'partials.navigation' )

			<div class="center_wrapper container-fluid pt-2">
				


				<div id="playbooks_list_wrapper col-5">
					<h3>Use playbooks</h3>
                    <form name="get_playbook_details_form" action="/playbooks" method="post" class="row g-3 mt-3 mx-2">
						@csrf
                        <select class="form-select" name="selected_playbook">
                            <option selected><?php echo $playbook_selection; ?></option>
                            <?php echo $playbook_options; ?> <!-- Probably don't want to put real paths in your html ... -->
                        </select>
                        <!--<select class="form-select" name="selected_host">
                            <option selected>Select a host.</option>
                            <?php #echo $host_options; ?>
                        </select> -->
						<div>
    						<input type="submit" name="submit" class="btn btn-primary" value="Get details">
						</div>
                    </form>
                    <form name="use_playbooks_form" action="/run_playbook" method="post" class="row g-3 mx-2">
						@csrf
                        <?php echo $variable_options; ?>
                    </form>
				</div>
				
				<div class="topology_map_wrapper col-6">
                    
                </div>

		</div>
	</div>

	<script src="/helper.js"></script>
	<!-- Bootstrap javascript -->
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js" integrity="sha384-w76AqPfDkMBDXo30jS1Sgez6pr3x5MlQ1ZAGC+nuZB+EYdgRZgiwxhTBTkF7CXvN" crossorigin="anonymous"></script>
	</body>
</html>
