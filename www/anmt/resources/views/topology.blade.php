<?php
session_start();

include(app_path().'/Includes/config.php');

$page_title = 'Topology';
$current_page = strtolower($page_title);




use Illuminate\Support\Facades\DB;

// Instance input.
$routers = DB::select('SELECT * FROM routers ORDER BY router_ip ASC');


$router_selection_input = '';

$router_selection_input = $router_selection_input . '<label class="input-group-text" for="instance_select">Instance</label>';
$router_selection_input = $router_selection_input . '<select class="form-select" id="instance_select" onchange="topology_selection()">';

$i = 0;
foreach ($routers as $router) {
	$router_ip_string = explode('.', $router->router_ip);
	$router_ip_string = implode('_', $router_ip_string);

	if ($i == 0) {
		$router_selection_input = $router_selection_input . '<option selected value="' . $router_ip_string . '">' . $router->router_ip . '</option>';
	}
	else {
		$router_selection_input = $router_selection_input . '<option value="' . $router_ip_string . '">' . $router->router_ip . '</option>';
	}
	$i = $i + 1;
}
$router_selection_input = $router_selection_input . '</select>';


// Date input.
$timestamps = DB::select('SELECT DISTINCT timestamp FROM hosts ORDER BY 1 DESC');

$time_selection_input = '';

$time_selection_input = $time_selection_input . '<label class="input-group-text" for="time_select">Time</label>';
$time_selection_input = $time_selection_input . '<select class="form-select" id="time_select" onchange="topology_selection()">';

$i = 0;
foreach ($timestamps as $timestamp) {
	$timestamp_string = explode('.', $timestamp->timestamp);
	$timestamp_string = implode('_', $timestamp_string);

	if ($i == 0) {
		$time_selection_input = $time_selection_input . '<option selected value="'. $timestamp_string . '">(Latest) ' . date('Y-m-d H:i:s T', (float)($timestamp->timestamp)) . '</option>';
	}
	else {
		$time_selection_input = $time_selection_input . '<option value="' . $timestamp_string . '">' . date('Y-m-d H:i:s T', (float)($timestamp->timestamp)) . '</option>';
	}
	$i = $i + 1;
}

$time_selection_input = $time_selection_input . '</select>';

?>

<!DOCTYPE html>
<html lang="en">
	<head>
		<?php include(base_path() . '/resources/views/partials/header.blade.php'); ?>
	</head>
	<body>

		<script>
			function topology_selection()
			{
				var instance = document.getElementById("instance_select").value;
				var timestamp = document.getElementById("time_select").value;
				var map = document.getElementById("topology_map");

				console.log(instance);
				
				if (instance != '' && timestamp != '') {
					map.src = "topology_" + instance + "_" + timestamp + ".html";
				}
			}

			document.addEventListener("DOMContentLoaded", function() {
				topology_selection();
			});
			</script>
		<div class="main_wrapper">
		
			@include( 'partials.navigation' )

			<div class="center_wrapper container-fluid">        
				
					
				<div class="topology_wrapper container-fluid" style="height: 100vh">
					<div class="topology_options row mt-3">
						<div class="input-group col">
							<?php echo $router_selection_input?>
						</div>
						<div class="input-group col">
							<?php echo $time_selection_input?>
						</div>
					</div>

					<iframe id="topology_map" style="height: 100%"
						src="/topology_placeholder.html"
						name="Topology"
						scrolling="no"
						frameborder="2px solid black"
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
