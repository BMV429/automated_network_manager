<html>
	<head>
		<title>Automated Network Managing Tool</title>
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
				<div class="host_list" style="display:none">
					<h2>Hosts</h2>
					<ul>
						<li>10.0.x.x</li>
						<li>10.0.x.x</li>
						<li>10.0.x.x</li>
						<li>10.0.x.x</li>
						<li>10.0.x.x</li>
						<li>10.0.x.x</li>
						<li>10.0.x.x</li>
						<li>10.0.x.x</li>
					</ul>
					<h2>Networks</h2>
					<ul>
						<li>10.0.0.0/24</li>
						<li>10.0.1.0/24</li>
						<li>10.0.2.0/24</li>
					</ul>
				</div>

				<div id="button_panel" class="row g-3">
					<h3>Actions</h3>
					<a href="/add_device.php"><button type="button" class="btn btn-primary col-md-4">Add device (manually)</button></a>
					<a href="/update_topology.php"><button type="button" class="btn btn-primary col-md-4">Update topology</button></a>
					<a href="/show_hosts.php"><button type="button" class="btn btn-primary col-md-4">Show list of hosts</button></a>
					<a href="/use_playbooks.php"><button type="button" class="btn btn-primary col-md-4">Execute playbooks</button></a>
					<a href="/logs.php"><button type="button" class="btn btn-primary col-md-4">Show logs</button></a>

					<h3>Links</h3>
					<a href="/goto_prometheus.php"><button type="button" class="btn btn-danger col-md-3">Prometheus</button></a>
					<a href="/goto_grafana.php"><button type="button" class="btn btn-danger col-md-3">Grafana</button></a>
				</div>

				<div class="topology_map_wrapper">
					<iframe id="topology_map"
						src="current_topology.html"
						name="Topology"
						scrolling="no"
						frameborder="0"
						height="100%"
						width="100%"
					> </iframe>
				</div>
		</div>
	</div>

	<script src="helper.js"></script>
	<!-- Bootstrap javascript -->
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js" integrity="sha384-w76AqPfDkMBDXo30jS1Sgez6pr3x5MlQ1ZAGC+nuZB+EYdgRZgiwxhTBTkF7CXvN" crossorigin="anonymous"></script>
	</body>
</html>
