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
			error_log("Async off. What am i supposed to do?");
			exit;
		}

		$this->type = "log";
		if(!$result = $this->cache->get()) {
				error_log("Error with cache.");
				exit;
		} else {
// TODO: if is shortlink was deleted?
			if(!empty($result)) {
				$this->db = new db(MYSQL_SERVER, MYSQL_DATABASE, MYSQL_USER, MYSQL_PASSWORD);
				if(!$isConnected) {
					error_log("Error with db.");
					exit;
				}
				foreach($result as $row) {
					$result = $this->db->insertRow("INSERT INTO `stats` (`shorturl`, `ip`, `useragent`, `referer`, `timestamp`, `processTime`) VALUES (?, ?, ?, ?, ?, ?) ;", 
						array($row->shorturl, $row->ip, $row->useragent, $row->referer, $row->timestamp, $row->processTime));
// TODO: WE NEED TO CHECK RETURN AND RETURN BOOL STATUS
					$result = $this->db->insertRow("UPDATE `data` SET `clicks` = `clicks` + 1 WHERE `shorturl` = ? ;", 
						array($row->shorturl));
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
