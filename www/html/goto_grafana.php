<?php
$redirect_url = "http://10.0.1.30:3000"
echo "Something went wrong while redirecting you to Grafana. You can manually go to " . $redirect_url;


header("Location: $redirect_url"); // Use a j2 conf file variable (ansible).
exit();
?>