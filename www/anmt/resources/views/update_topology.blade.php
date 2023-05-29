<?php 
#echo "Automatic redirection failed. Please manually go back.";

#$command_update_topology = "python3 /home/sb/automated_network_manager/topology_mapper/mapper_main.py 10.0.0.5 bram cisco"; // Find a way to not hardcode the password.
#$shell_output = shell_exec($command_update_topology);
#echo $shell_output;
#header("Location: /");
#exit();

include(app_path().'/Includes/config.php');
$page_title = 'Update topology';
$current_page = strtolower($page_title);

# -- Show hosts.
$hosts = array('Windows', 'Linux', 'Cisco router');
$host_options = '';
foreach ($hosts as $i => $host) {
    $host_options = $host_options . '<option value="' . $i . '">' . $host . '</option>';
}

$routers = DB::select('select * from routers');

$variable_options = "";
$playbook_selection = "Select a playbook.";

$device_table = get_device_list_short();
#populate_devices_table();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
	echo "submitted";
}
?>

<!DOCTYPE html>
<html>
	<head>
		<?php include(base_path() . '/resources/views/partials/header.blade.php'); ?>
	</head>
	<body>
    <script>
      
      router_counter.value = 1;

      function rm_rt_field(field_id) {
        var fields_list = document.getElementById("router_credential_fields");

        var field_id = "rt_field_" + field_id;
        var selected_field = document.getElementById(field_id)

        selected_field.parentNode.removeChild(selected_field);
      }

      function add_rt_field() {
        var i = 0, field_count = 1;
        var fields_list = document.getElementById("router_credential_fields");

        //while(fields_list.getElementsByTagName('li') [i++]) field_count++;


        field_count = fields_list.childElementCount + 1;

        var router_counter = document.getElementById("router_counter");

        router_counter.value = field_count;

        var field_html = '<div class="col-md-1 px-2 mt-1"><input class="form-control" style="text-align: center;" type="text" id="router_id" name="router_count" value="' + field_count + '" aria-label="readonly input" readonly></div>'
        
        field_html += `
            <div class="col-md-3 px-2 mt-1">
              <!--<label for="router_ip" class="form-label">Router IP</label>-->`

        field_html += '<input type="text" class="form-control" id="router_ip" name="router_ip" placeholder="10.0.' + field_count + '.1" required>';
        field_html += `
              <div class="invalid-feedback">
                Please provide a valid ip address.
              </div>
            </div>
            <div class="col-md-3 px-2 mt-1">
              <!--<label for="router_username" class="form-label">Router username</label>-->
              <input type="text" class="form-control" id="router_username" name="router_username" placeholder="username" required>
              <div class="invalid-feedback">
                Please provide a valid username.
              </div>
            </div>
            <div class="col-md-3 px-2 mt-1">
              <!--<label for="router_password" class="form-label">Router password</label>-->
              <input type="text" class="form-control" id="router_password" name="router_password" placeholder="*******" required>
              <div class="invalid-feedback">
                Please provide a valid password.
              </div>
            </div>
        `;

        field_html += '<button type="button" id="remove_field_' + field_count + '" onclick="rm_rt_field(' + field_count + ')">-</button>';

        var field_id = "rt_field_" + field_count;

        var new_field = document.createElement("li");
        new_field.setAttribute("id", field_id);
        
        new_field.innerHTML = field_html;

        fields_list.appendChild(new_field);
      }

    </script>
		<div class="main_wrapper">
		
      @include( 'partials.navigation' )

			<div class="center_wrapper container-fluid pt-2">
				<div id="update_topology_wrapper col">
          <h3>Update topology</h3>

          <!-- ALERTS -->
          @if (\Session::has('credentials'))
          <div class="alert alert-info pb-0">
            <p class="pbalert">{!! \Session::get('credentials') !!}</p>
          </div>
				  @elseif (\Session::has('failure'))
          <div class="alert alert-danger pb-0">
            <p class="pbalert">{!! \Session::get('failure') !!}</p>
          </div>
          @endif
          
          <!-- RETRIEVE STORED CREDENTIALS 
          <a href="/get_routers"><button type="button" class="btn btn-danger my-3">
            Decrypt credentials
          </button></a>-->

          <!-- STORED CREDENTIALS -->
          <ul id="router_credential_fields">
            <h5 class="mt-4">Registered routers</h5>

            @if (!empty($routers))
            @foreach ($routers as $router)

            
            <li>
              <!--<div class=" col-md-1 form-check form-switch">
                <input class="form-check-input" type="checkbox" role="switch" id="update_check" checked>
              </div>-->
              <div class="col-md-1 px-2 mt-1">
                <input class="form-control" type="text" style="text-align: center;" id="router_id" name="router_count" value="{{ $router->router_id }}" aria-label="readonly input" readonly>
              </div>
              <div class="col-md-3 px-2 mt-1">
                <input type="text" class="form-control" id="router_ip" name="router_ip" value="{{ $router->router_ip }}" placeholder="10.0.0.5" readonly>
                <div class="invalid-feedback">
                  Please provide a valid ip address.
                </div>
              </div>
              <div class="col-md-3 px-2 mt-1">
                <input type="text" class="form-control" id="router_username" name="router_username" value="{{ $router->router_username }}" placeholder="username" readonly>
                <div class="invalid-feedback">
                  Please provide a valid username.
                </div>
              </div>
              <div class="col-md-3 px-2 mt-1">
                <input type="text" class="form-control" id="router_password" name="router_password" placeholder="*******" readonly>
                <div class="invalid-feedback">
                  Please provide a valid password.
                </div>
              </div>
              <a href="/delete_router/{{ $router->router_id }}"><button type="button" class="btn-close" aria-label="Remove"></button></a>
            </li>
            @endforeach
            @endif
          </ul>
              
          <!-- ADD ROUTER -->
          <form name="update_topology_form" action="/store_router" method="post" class="">
          @csrf
            <ul id="router_credential_fields">
              <li>
                <div class="col-md-1 px-2 mt-1">
                </div>
                <div class="col-md-3 px-2 mt-1">
                  <input type="text" class="form-control" id="router_ip" name="router_ip" placeholder="Hostname or IP" required>
                  <div class="invalid-feedback">
                    Please provide a valid ip address.
                  </div>
                </div>
                <div class="col-md-3 px-2 mt-1">
                  <input type="text" class="form-control" id="router_username" name="router_username" placeholder="Router username" required>
                  <div class="invalid-feedback">
                    Please provide a valid username.
                  </div>
                </div>
                <div class="col-md-3 px-2 mt-1">
                  <input type="password" class="form-control" id="router_password" name="router_password" placeholder="Router password" required>
                  <div class="invalid-feedback">
                    Please provide a valid password.
                  </div>
                </div>
                <div class="col-md-3">
                  <button type="submit" class="btn btn-primary" id="add_router_button">Add router</button>
                </div>
              </li>
            </ul>
          </form>
         
          <!-- UPDATE TOPOLOGY -->
          <a href="/update_topology_map"><input type="button" class="btn btn-primary col-md-2 mt-4 mx-2" value="Update topology"></a>
				</div>
		</div>
	</div>

	<script src="/helper.js"></script>
	<!-- Bootstrap javascript -->
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js" integrity="sha384-w76AqPfDkMBDXo30jS1Sgez6pr3x5MlQ1ZAGC+nuZB+EYdgRZgiwxhTBTkF7CXvN" crossorigin="anonymous"></script>
	</body>
</html>


