<?php
$redirect_url = "http://anmt:9090"
echo "Something went wrong while redirecting you to Prometheus. You can manually go to " . $redirect_url;


header("Location: $redirect_url"); // Use a j2 conf file variable (ansible).
exit();
?>
