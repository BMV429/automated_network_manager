<?php
session_start();

include(app_path().'/Includes/config.php');

$page_title = 'Event Logs';
$current_page = strtolower($page_title);
$logs_table = get_logs();

?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <?php include(base_path() . '/resources/views/partials/header.blade.php'); ?>
    </head>
    <body>
        <div class="main_wrapper">
		
            @include( 'partials.navigation' )
            
            <div class="center_wrapper container-fluid">
                <div class="full_logs_wrapper container-fluid">
                    <?php echo $logs_table; ?>
                </div>
            </div>
        </div>

        
        <script src="/helper.js"></script>
        <!-- Bootstrap javascript -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js" integrity="sha384-w76AqPfDkMBDXo30jS1Sgez6pr3x5MlQ1ZAGC+nuZB+EYdgRZgiwxhTBTkF7CXvN" crossorigin="anonymous"></script>
    </body>
</html>
