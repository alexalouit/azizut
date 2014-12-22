<?php
/**
 * Cron class, do some async things in background
 * @author: Alexandre Alouit <alexandre.alouit@gmail.com>
 */

/*
 * Class autoloader
 */
function __autoload($classname) {
	$filename = "class." . $classname . ".php";
	include_once($filename);
}

class cron {
	private $db = NULL;
	private $cache = NULL;

	/*
	 * Constructor
	 */
	public function __construct() {
		require_once './config/config.php';
		if($this->cache = new cache()) {
		} else {
			error_log("Error with cache.");
			exit;
		}
		if(!ASYNC) {
			@error_log("Async off. What am i supposed to do?\n", 3, DEBUG);
			exit;
		}

		$this->cache->type = "log";
		$result = $this->cache->get();
		if(!isset($result) OR $result === FALSE) {
				@error_log("Error with cache (index is present? empty?).\n", 3, DEBUG);
				exit;
		} elseif(empty($result)) {
			@error_log("Index is empty.\n", 3, DEBUG);
		} else {
// TODO: if is shortlink was deleted?
			if(!empty($result) && $result !== FALSE) {
				$this->db = new db(MYSQL_SERVER, MYSQL_DATABASE, MYSQL_USER, MYSQL_PASSWORD);
// TODO: fix->
//				if(!$this->db->$isConnected) {
//					error_log("Error with db.");
//					exit;
//				}
				foreach($result as $row) {
					$return1 = $this->db->insertRow("INSERT INTO `stats` (`shorturl`, `ip`, `useragent`, `referer`, `timestamp`, `processTime`) VALUES (?, ?, ?, ?, ?, ?) ;", 
						array($row->shorturl, $row->ip, $row->useragent, $row->referer, $row->timestamp, $row->processTime));
					$return2 = $this->db->insertRow("UPDATE `data` SET `clicks` = `clicks` + 1 WHERE `shorturl` = ? ;", 
						array($row->shorturl));

					if($return1 && $return2) {
					} else {

						error_log("We have an error in cron process.");
						return FALSE;
					}
				}
			}
		}
	}

	/*
	 * Destructor
	 */
	public function __destruct() {
	}



}

?>
