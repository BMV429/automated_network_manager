import sqlite3

class Database():

    db_path = "./vis.db"

    def __init__(self):
        self.connection = sqlite3.Connection(self.db_path)
        self.cursor = self.connection.cursor()

        # Check if tables exist.
        res = self.cursor.execute("SELECT name FROM sqlite_master WHERE name='hosts'")
        if (res.fetchone() is None): # If the table 'hosts' does not exist.
            self.create_tables()
        
    def execute(self, query, data=""):
        print(query)
        print(data)
        self.cursor.execute(query, data)

    def get_latest_hosts(self):
        # Get most recent data.
        query = "SELECT * FROM hosts WHERE timestamp = (SELECT MAX(timestamp) FROM hosts);"
        
        self.execute(query)
        rows = self.cursor.fetchall()

        return rows


    def commit(self):
        self.connection.commit()



    def create_tables(self):
        query = f"CREATE TABLE hosts(host_id int, hostname text, ip_list text, mac_list text, port int, router_id int, timestamp text, default_gateway text, dns_server text, serial_number text, device_model text)"
        self.execute(query)
        self.commit()


    # https://codereview.stackexchange.com/questions/182700
    def __enter__(self):
        return self

    def __exit__(self, ext_type, exc_value, traceback):
        self.cursor.close()
        if isinstance(exc_value, Exception):
            self.connection.rollback()
        else:
            self.connection.commit()

        self.connection.close
    ###