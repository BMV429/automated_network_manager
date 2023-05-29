<?php

if ($_SERVER["REQUEST_METHOD"] == "POST") {
	var_dump($_POST);
	//var_dump($_SERVER);
	//store_device_data();
	echo $_POST['test_field'];
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

			<div class="title_wrapper">
				<h1>ANMT</h1>
				<p>Automated Network Managing Tool</p>
			</div>

			<div class="center_wrapper">
				

				<div id="new_device_form_wrapper">
                    <form name="new_device_form" action="add_device_test.php" method="post"> <!-- class="row g-3"> -->
                        
						<span class="invalid-feedback"></span>

						<div class="mb-3 col-md-4">
							<label for="test_field" class="form-label">test_field</label>
							<input type="text" class="form-control" id="test_field" placeholder="test" name="test_field">
						</div>
                        <button type="submit">Add</button>
                    </form>
				</div>
		</div>
	</div>

	<script src="/helper.js"></script>
	<!-- Bootstrap javascript -->
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js" integrity="sha384-w76AqPfDkMBDXo30jS1Sgez6pr3x5MlQ1ZAGC+nuZB+EYdgRZgiwxhTBTkF7CXvN" crossorigin="anonymous"></script>
	</body>
</html>
