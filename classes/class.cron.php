<?php
/**
 * Cron class, do some async things in background
 * @author: Alexandre Alouit <alexandre.alouit@gmail.com>
 */

class cron {
	private $db = NULL;
	private $cache = NULL;

	/*
	 * Class autoloader
	 */
	private function __autoload($classname) {
		$filename = "class." . $classname . ".php";
		include_once($filename);
	}

	/*
	 * Constructor
	 */
	public function __construct() {
		require_once './config/config.php';
		if(is_null(CACHE_TYPE)) {
			error_log("");
			exit;
		}
		if(!ASYNC) {
			error_log("");
			exit;
		}
		if(!is_int(QPP)) {
			error_log("");
			exit;
		}

		$this->cache = new cache();
		$this->cache->name = "testVar";
		if(!$this->cache->insert()) {
			error_log("");
			exit;
		} else {
			$this->cache->delete();
			if(!$result = $this->cache->getAsync()) {
				error_log("");
				exit;
			} else {
				if(!is_null($result)) {
					$this->db = new db(MYSQL_SERVER, MYSQL_DATABASE, MYSQL_USER, MYSQL_PASSWORD);
					if(!$isConnected) {
						error_log("");
						exit;
					}
					foreach($result as $row) {
						$result = $this->db->insertRow("INSERT INTO `stats` (`shorturl`, `ip`, `useragent`, `referer`, `timestamp`) VALUES (?, ?, ?, ?, ?) ;", 
							array($this->shorturl, $this->ip, $this->useragent, $this->referer, $this->timestamp));
// TODO: WE NEED TO CHECK RETURN AND RETURN BOOL STATUS
						$result = $this->db->insertRow("UPDATE `data` SET `clicks` = `clicks` + 1 WHERE `shorturl` = ? ;", 
						array($this->shorturl));
						$this->cache->delete();
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
