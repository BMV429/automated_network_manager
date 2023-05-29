<?php

include(app_path().'/Includes/config.php');
$page_title = 'Playbook output';
$current_page = strtolower($page_title);

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
			<div id="playbook_output col-5">
				<h3>Output</h3>
				@if (\Session::has('playbook_success'))
				<div class="alert alert-success">
					<p class="pbalert">Playbook executed successfully.</p>
				</div>
				<div class="playbook_output_box container-fluid">
					<pre>{!! \Session::get('playbook_success') !!}</pre>
				</div>
				@elseif (\Session::has('playbook_failure'))
				<div class="alert alert-danger">
					<p class="pbalert">Failure when executing playbook.</p>
				</div>
				<div class="playbook_output_box container-fluid">
					<pre>{!! \Session::get('playbook_failure') !!}</pre>
				</div>
				@else
				<div class="playbook_output_box container-fluid">
					<p>No output available.</p>
				</div>
				@endif
			</div>
		</div>
	</div>

	<script src="/helper.js"></script>
	<!-- Bootstrap javascript -->
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js" integrity="sha384-w76AqPfDkMBDXo30jS1Sgez6pr3x5MlQ1ZAGC+nuZB+EYdgRZgiwxhTBTkF7CXvN" crossorigin="anonymous"></script>
	</body>
</html>
