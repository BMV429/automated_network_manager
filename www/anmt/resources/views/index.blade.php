<?php

include(app_path().'/Includes/config.php');
$page_title = 'Home';
$current_page = strtolower($page_title);

?>

<html>
	<head>
        <?php include(base_path() . '/resources/views/partials/header.blade.php'); ?>
	</head>
	<body>
	<div class="main_wrapper">

		<!-- NAVBAR -->
		@include( 'partials.navigation' )
		

		<div class="center_wrapper">
			<div class="title_wrapper mt-4">
				<h1>ANMT</h1>
				<p>Automated Network Managing Tool</p>
			</div>
		</div>
	</div>

	<script src="../js/helper.js"></script>
	<!-- Bootstrap javascript -->
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js" integrity="sha384-w76AqPfDkMBDXo30jS1Sgez6pr3x5MlQ1ZAGC+nuZB+EYdgRZgiwxhTBTkF7CXvN" crossorigin="anonymous"></script>
	</body>
</html>
