<?php
session_start();

// Insert default config.
include(app_path().'/includes/config.php');

$page_title = 'Add device';
$current_page = strtolower($page_title);

// Show inventory on page load.
$device_table = get_device_list_short();

// On form submit.
if ($_SERVER["REQUEST_METHOD"] == "POST") {
	echo "post works";
}
?>

<!DOCTYPE html>
<html>
	<head>
		<?php include('/var/www/html/resources/views/partials/header.blade.php'); ?>
	</head>
	<body>
		<div class="main_wrapper">
		
			<div class="navigation_bar">
				<img src="logo.png" alt="Company logo">
			</div>

			<div class="center_wrapper">
				<div id="new_device_form_wrapper">

                <form method="POST" action="/test">
                    @csrf
                 
                    <label for="username">Post Title</label>
                    <input id="username"
                        type="text"
                        class="@error('username') is-invalid @enderror">
                    
                    @error('username')
                        <div class="alert alert-danger">{{ $message }}</div>
                    @enderror
                    
                </form>

                </div>
		    </div>
	    </div>

	<script src="{{ asset('js/helper.js') }}"></script>
	<!-- Bootstrap javascript -->
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js" integrity="sha384-w76AqPfDkMBDXo30jS1Sgez6pr3x5MlQ1ZAGC+nuZB+EYdgRZgiwxhTBTkF7CXvN" crossorigin="anonymous"></script>
	</body>
</html>
