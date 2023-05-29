<?php
session_start();

require_once '/var/www/anmt/html/config.php';

$page_title = 'Add device';
$current_page = strtolower($page_title);
$device_table = get_device_list_long();
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
