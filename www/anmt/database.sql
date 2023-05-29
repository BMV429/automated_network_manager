CREATE TABLE logs (
    log_id INTEGER PRIMARY KEY AUTOINCREMENT,
    timestamp INTEGER,
    action TEXT,
    target TEXT,
    user TEXT,
    status TEXT,
    details TEXT
);

CREATE TABLE routers (
    router_id INTEGER PRIMARY KEY AUTOINCREMENT,
    router_ip TEXT,
    router_username TEXT,
    router_secret TEXT
);

CREATE TABLE hosts (
    host_id_auto INTEGER PRIMARY KEY AUTOINCREMENT,
    host_id INTEGER, 
    hostname TEXT, 
    ip_list TEXT, 
    mac_list TEXT, 
    port INTEGER, 
    router_id INTEGER, 
    timestamp TEXT, 
    default_gateway TEXT, 
    dns_server TEXT, 
    serial_number TEXT, 
    device_model TEXT, 
    operating_system TEXT
);

CREATE TABLE manual_hosts (
    entry_id INTEGER PRIMARY KEY AUTOINCREMENT,
    IPv4 TEXT,
    MAC TEXT,
    OS TEXT,
    model TEXT,
    sn TEXT,
    device_username TEXT,
    device_password TEXT,
    connected_device_ip TEXT,
    connected_device_port TEXT
);