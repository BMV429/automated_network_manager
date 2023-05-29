<!-- Sidebar -->
<nav id="sb_menu" class="collapse d-lg-block sidebar collapse bg-white">
    <div class="position-sticky">
        <a href="/" class="text-center head_nav_item">
        <h2>ANMT</h2>
        </a>
        
        <div class="mx-3 mt-1">

        <p class="breakline pt-4">General</p>

        <a href="/add_device" class="py-2 <?php if (Request::is('add_device*')) { echo 'active bg-success'; } ?>">
        <span>Add device</span></a>
        
        <a href="/topology" class="py-2 <?php if (Request::is('topology*')) { echo 'active bg-success'; } ?>">
        <span>Topology</span></a>
        
        <a href="/inventory" class="py-2 <?php if (Request::is('inventory*')) { echo 'active bg-success'; } ?>">
        <span>Inventory</span></a>
        
        <a href="/logs" class="py-2 <?php if (Request::is('logs*')) { echo 'active bg-success'; } ?>">
        <span>Logs</span></a>
        
        <a href="/playbooks" class="py-2 <?php if (Request::is('playbooks*')) { echo 'active bg-success'; } ?>">
        <span>Use playbooks</span></a>
        
        <a href="/update_topology" class="py-2 <?php if (Request::is('update_topology*')) { echo 'active bg-success'; } ?>">
        <span>Update topology</span></a>

        <p class="breakline pt-4">Links</p>
        
        <a href="/prometheus" class="py-2">
        <span>Prometheus</span></a>
        
        <a href="/grafana" class="py-2">
        <span>Grafana</span></a>
        
        <!-- Authentication -->
        <p class="breakline pt-4">User</p>
    
        @if (\Auth::user())
        <a href="/profile">
            {{ Auth::user()->name }}
        </a>

        <form method="POST" action="/logout">
            @csrf
            <a href="/logout" onclick="event.preventDefault(); this.closest('form').submit();">
                Log Out
            </a>
        @else
        <a href="/login">
            Log In
        </a>
        @endif
        </form>
        </div>
    </div>
</nav>