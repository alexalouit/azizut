<?php
/**
 * SQL classes, rapid, convenient, and reliable.
 * @author Alex Alouit <alexandre.alouit@gmail.com>
 * inspired from Elias Van Ootegem
 */

class db {
	public $isConnected;
	protected $datab;

	public function __construct($mysql_server, $mysql_database, $mysql_user, $mysql_password) {
		if(!isset($mysql_server) OR !isset($mysql_database) OR !isset($mysql_user) OR !isset($mysql_password)) {
			die("Database config?");
		}

		$this->isConnected = true;
		try { 
			$this->datab = new PDO("mysql:host=".$mysql_server.";dbname=".$mysql_database.";charset=utf8", $mysql_user, $mysql_password, array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'));
			$this->datab->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$this->datab->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
			$this->datab->setAttribute(PDO::ATTR_EMULATE_PREPARES, FALSE);
		} 
		catch(PDOException $e) { 
			$this->isConnected = false;
			throw new Exception($e->getMessage());
		}
	}

	public function __destruct() {
		$this->Disconnect();
	}

	public function Disconnect() {
		$this->datab = null;
		$this->isConnected = false;
	}

	public function getRow($query, $params=array()) {
		try { 
			$stmt = $this->datab->prepare($query); 
			$stmt->execute($params);
			return $stmt->fetch(PDO::FETCH_OBJ);
			} catch(PDOException $e) {
				throw new Exception($e->getMessage());
		}
	}

	public function getRows($query, $params=array()) {
		try { 
			$stmt = $this->datab->prepare($query); 
			$stmt->execute($params);
			return $stmt->fetchAll(PDO::FETCH_OBJ);
			} catch(PDOException $e) {
				throw new Exception($e->getMessage());
		}
	}

	public function insertRow($query, $params) {
		try { 
			$stmt = $this->datab->prepare($query); 
			$stmt->execute($params);
			} catch(PDOException $e) {
				throw new Exception($e->getMessage());
		}
	}

	public function updateRow($query, $params) {
		return $this->insertRow($query, $params);
	}

	public function deleteRow($query, $params) {
		return $this->insertRow($query, $params);
	}


}

?>
