<?php
session_start();

require_once '/var/www/anmt/html/config.php';

$page_title = 'Event Logs';
$current_page = strtolower($page_title);

$logs_table = get_logs();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ANMT - Logs</title>

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
					<?php echo $logs_table; ?>
				</div>
        </div>
</div>
</body>
</html>