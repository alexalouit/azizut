<?php
/**
 * Azizut API (core) class, design to be fast as possible
 * @author Alex Alouit <alexandre.alouit@gmail.com>
 */

class api {
	private $return = "";
	private $host = NULL;
	private $domain = NULL;
	private $ip = "unknown";
	private $clicks = 0;
	private $uniquid = NULL;
	private $timestamp = NULL;
	private $referer = "unknown";
	private $useragent = "unknown";
	private $username = NULL;
	private $password = NULL;
	private $url = NULL;
	private $shorturl = NULL;
	private $description = "unknown";
	private $start = 0;
	private $limit = 500;
	private $db = NULL;
	private $cache = NULL;
	private $processTime = 0;

	/*
	 * Class autoloader
	 */
	private function __autoload($classname) {
		$filename = "class." . $classname . ".php";
		include_once($filename);
	}

	/*
	 * Constructor, dispatcher (build the way)
	 */
	public function __construct() {
		$this->processTime = microtime(1);
		require_once './config/config.php';

		$this->timestamp = date("o-m-d H:i:s");
		$this->ip = $_SERVER['REMOTE_ADDR'];
		$this->domain =  $_SERVER['HTTP_HOST'] . "/";
		if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == "on") {
			$this->host = "https://" . $this->domain;
		} else {
			$this->host = "http://" . $this->domain;
		}

		$this->shorturl = $_SERVER['REQUEST_URI'];
		$this->shorturl = substr($this->shorturl, 1);
		if(substr($this->shorturl, -3) == ".qr") {
			$qrcode = TRUE;
			$this->shorturl = substr($this->shorturl, 0, -3);
		}

// TODO: CHECK PATTERN FOR SHORTURL, ELSE, GENERATE DIRECTLY 404!
		if(!is_null(CACHE_TYPE)) {
			$this->cache = new cache();
		}

		if(!$this->get()) {

			$this->return = "/error/404.html";
// TODO: insert 404 in cache AND log it (log after for accurate process time
			exit;
		} else {
			if(isset($_SERVER['HTTP_DNT']) && $_SERVER['HTTP_DNT'] == 1) {
			} else { 
				if(!empty($_SERVER['REMOTE_ADDR'])) {
					$this->ip = $_SERVER['REMOTE_ADDR'];
				}
				if(!empty($_SERVER['HTTP_REFERER'])) {
					$this->referer = $_SERVER['HTTP_REFERER'];
				}
				if(!empty($_SERVER['HTTP_USER_AGENT'])) {
					$this->useragent = $_SERVER['HTTP_USER_AGENT'];
				}
			}

			if(isset($qrcode) && $qrcode) {
				$this->qr();
			}

			if(!$this->log()) {
				error_log("We have an error in process, guest was not save.");
			}

			$this->return = $this->url;
			exit;
		}
	}

	/*
	 * Desctructor, return the content
	 * @return: http header for guests
	 */
	public function __destruct() {
		header("HTTP/1.1 301 Moved Permanently");
		header("Location: {$this->return}");

		exit;
	}

	/*
	 * QRCode returner
	 * @return: (bool)
	 * @apiReturn: (string) qrcode link
	 */
	private function qr() {
		$this->return = "http://chart.apis.google.com/chart?chs=150x150&cht=qr&chld=M&chl=" . $this->host . $this->shorturl;
		exit;
	}

	/*
	 * Log an guest for stats
	 * @return (bool)
	 */
	private function log() {
		$this->processTime = ($this->processTime) - (microtime(1));
		if(!is_null(CACHE_TYPE) && ASYNC) {
			$cache = new cache();
			if(!$this->cache->insertAsync($this)) {
			error_log("Error with cache, insert directly in DB.");
			$this->db = new db(MYSQL_SERVER, MYSQL_DATABASE, MYSQL_USER, MYSQL_PASSWORD);
			$result = $this->db->insertRow("INSERT INTO `stats` (`shorturl`, `ip`, `useragent`, `referer`, `timestamp`) VALUES (?, ?, ?, ?, ?) ;", 
				array($this->shorturl, $this->ip, $this->useragent, $this->referer, $this->timestamp));
// TODO: WE NEED TO CHECK RETURN AND RETURN BOOL STATUS
			$result = $this->db->insertRow("UPDATE `data` SET `clicks` = `clicks` + 1 WHERE `shorturl` = ? ;", 
				array($this->shorturl));
		} else {
				$this->db = new db(MYSQL_SERVER, MYSQL_DATABASE, MYSQL_USER, MYSQL_PASSWORD);
			$result = $this->db->insertRow("INSERT INTO `stats` (`shorturl`, `ip`, `useragent`, `referer`, `timestamp`) VALUES (?, ?, ?, ?, ?) ;", 
				array($this->shorturl, $this->ip, $this->useragent, $this->referer, $this->timestamp));
// TODO: WE NEED TO CHECK RETURN AND RETURN BOOL STATUS
			$result = $this->db->insertRow("UPDATE `data` SET `clicks` = `clicks` + 1 WHERE `shorturl` = ? ;", 
				array($this->shorturl));
		}
	}

	/*
	 * Search URL to serve from cache & db
	 * @return (bool)
	 * @apiReturn: (object) result(s)
	 */
	private function get() {

		if(!is_null(CACHE_TYPE)) {
			$cache = new cache();
			if(!$this->cache->get($this->shorturl)) {
				error_log("Error with cache, " . $this->shorturl . " not found. Search in DB.");
				$this->db = new db(MYSQL_SERVER, MYSQL_DATABASE, MYSQL_USER, MYSQL_PASSWORD);
				$result = $this->db->getRow("SELECT * FROM `data` WHERE `shorturl` = ? ;", 
				array($this->shorturl));
		} else {
				$this->db = new db(MYSQL_SERVER, MYSQL_DATABASE, MYSQL_USER, MYSQL_PASSWORD);
				$result = $this->db->getRow("SELECT * FROM `data` WHERE `shorturl` = ? ;", 
				array($this->shorturl));
		}

		if(!$result OR empty($result->url)) {

			return FALSE;
		} else {
			$this->url = $result->url;
			$this->return->data = $result;

			return TRUE;
		}

	}


}

?>
