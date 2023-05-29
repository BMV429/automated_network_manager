<?php

/**
 * To log:
 * X Update topology.
 * X Ping device.
 * X Add device.
 * X Use playbook.
 */

class SQLiteConnectionn {
	private $pdo;

	public function connect() {
		if ($this->pdo == null) {
			#$this->pdo = new PDO("sqlite:" . Config::SQLITE_DB_PATH);
			$this->pdo = new PDO('sqlite:' . base_path() . '/database/anmt.db');
		}

		return $this->pdo;
	}
}

class SQLiteDBb {
	private $pdo;

	public function __construct($pdo) {
		$this->pdo = $pdo;
		$this->create_tables();
	}

	public function create_tables() {
		$query = "CREATE TABLE IF NOT EXISTS 
					logs(
						log_id INTEGER PRIMARY KEY AUTOINCREMENT,
						timestamp INTEGER,
						action TEXT,
						target TEXT,
						user TEXT,
						status TEXT,
						details TEXT
					);";

		$this->pdo->exec($query);
	}

	public function insert_log($action, $target, $user, $status, $details) {
		$date = new DateTimeImmutable();
		$timestamp = $date->getTimestamp();

		$query = "INSERT INTO logs (timestamp, action, target, user, status, details) VALUES (:timestamp, :action, :target, :user, :status, :details);";
		$stmt = $this->pdo->prepare($query);

		$stmt->bindValue(':timestamp', $timestamp);
		$stmt->bindValue(':action', $action);
		$stmt->bindValue(':target', $target);
		$stmt->bindValue(':user', $user);
		$stmt->bindValue(':status', $status);
		$stmt->bindValue(':details', $details);

		$stmt->execute();

		return $this->pdo->lastInsertId();
	}
}

function create_log_record($action, $target, $user, $status, $details) {
	// WIP - Get devices from vis.db
	try {
		// Connect to DB.
		$pdo = new PDO('sqlite:' . base_path() . '/database/anmt.db');
		$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

		// Create tables if they do not exist.
		#$query = 
		#$pdo->exec($query);
		// --------

		#$pdo = (new SQLiteConnectionn())->connect();
		$dbb = new SQLiteDBb($pdo);

		$dbb->create_tables();

		$log_id = $dbb->insert_log($action, $target, $user, $status, $details);
	
		// Close db connection.
		$pdo = null;
	}
	catch (PDOException $e) {
		echo $e->getMessage();
	}
}
?>
