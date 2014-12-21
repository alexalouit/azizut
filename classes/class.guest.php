<?php
/**
 * Azizut API (core) class, design to be fast as possible
 * @author Alex Alouit <alexandre.alouit@gmail.com>
 */

/*
 * Class autoloader
 */
function __autoload($classname) {
	$filename = "class." . $classname . ".php";
	include_once($filename);
}

class guest {
	private $return = "";
	private $host = NULL;
	private $ip = "unknown";
	private $clicks = 0;
	private $uniquid = NULL;
	private $timestamp = NULL;
	private $referer = "unknown";
	private $useragent = "unknown";
	private $url = NULL;
	private $shorturl = NULL;
	private $start = 0;
	private $limit = 500;
	private $db = NULL;
	private $cache = NULL;
	private $processTime = 0;
	private $qrcode = FALSE;
	private $html_404 = "/error/404.html";

	/*
	 * Constructor, dispatcher (build the way)
	 */
	public function __construct() {
		$this->processTime = microtime(1);
		require_once './config/config.php';

		$this->timestamp = date("o-m-d H:i:s");
		$this->ip = $_SERVER['REMOTE_ADDR'];

		$this->shorturl = $_SERVER['REQUEST_URI'];

		$this->shorturl = substr($this->shorturl, 1);
		if(substr($this->shorturl, -3) == ".qr") {
			$this->qrcode = TRUE;
			$this->shorturl = substr($this->shorturl, 0, -3);
		}

		$this->shorturl = str_replace("/", "", $this->shorturl);
		if(!preg_match("(\A[0-9a-zA-Z]{5}\z)", $this->shorturl)) {
			$this->html_404(FALSE);
		}

		if(CACHE) {
			$this->cache = new cache();
		}

		if(!$this->get()) {
			$this->html_404();
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

			if(isset($this->qrcode) && $this->qrcode) {
				$this->domain =  $_SERVER['HTTP_HOST'] . "/";
				if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == "on") {
					$this->host = "https://" . $this->domain;
				} else {
					$this->host = "http://" . $this->domain;
				}

				$this->qr();
			}

			if(!$this->log()) {
//				error_log("We have an error in process, guest was not save.");
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
	 * Deserve 404 to guest, insert in cache for flooding prevent, and log it.
	 * params: (bool) log
	 */
	private function html_404($log = TRUE) {
		$this->return = $this->html_404;
// TODO: insert 404 in cache AND log it (if bool) (log after for accurate process time)
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
// TODO: fix processtime
// TODO: return correct (bool) value
		$this->processTime = ($this->processTime) - (microtime(1));
		if(CACHE && ASYNC) {
			$this->cache->type = "log";
			$this->cache->data = json_encode($this);
			if(!$this->cache->insert()) {
				error_log("Error with cache, insert directly in DB.");
				$this->db = new db(MYSQL_SERVER, MYSQL_DATABASE, MYSQL_USER, MYSQL_PASSWORD);
				$result = $this->db->insertRow("INSERT INTO `stats` (`shorturl`, `ip`, `useragent`, `referer`, `timestamp`, `processtime`) VALUES (?, ?, ?, ?, ?, ?) ;", 
					array($this->shorturl, $this->ip, $this->useragent, $this->referer, $this->timestamp, $this->processTime));
// TODO: WE NEED TO CHECK RETURN AND RETURN BOOL STATUS
				$result = $this->db->insertRow("UPDATE `data` SET `clicks` = `clicks` + 1 WHERE `shorturl` = ? ;", 
					array($this->shorturl));
			}
		} else {
			$this->db = new db(MYSQL_SERVER, MYSQL_DATABASE, MYSQL_USER, MYSQL_PASSWORD);
			$result = $this->db->insertRow("INSERT INTO `stats` (`shorturl`, `ip`, `useragent`, `referer`, `timestamp`, `processtime`) VALUES (?, ?, ?, ?, ?, ?) ;", 
				array($this->shorturl, $this->ip, $this->useragent, $this->referer, $this->timestamp, $this->processTime));
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
		if(CACHE) {
			$this->cache = new cache();
			$this->cache->type = "redirect";
			$this->cache->key = $this->shorturl;
			$result = new stdClass;
			if(!$result->url = $this->cache->get()) {
				error_log("/" . $this->shorturl . " not found in cache. Search in DB.");
				$this->db = new db(MYSQL_SERVER, MYSQL_DATABASE, MYSQL_USER, MYSQL_PASSWORD);
				$result = $this->db->getRow("SELECT * FROM `data` WHERE `shorturl` = ? ;", 
					array($this->shorturl));
				$cacheIt = TRUE;
			} else {
				error_log("/" . $this->shorturl . " found in cache.");
			}
		} else {
			$this->db = new db(MYSQL_SERVER, MYSQL_DATABASE, MYSQL_USER, MYSQL_PASSWORD);
			$result = $this->db->getRow("SELECT * FROM `data` WHERE `shorturl` = ? ;", 
				array($this->shorturl));
		}

		if(!$result OR empty($result->url)) {

			return FALSE;
		} else {
			$this->url = $result->url;

			if(isset($cacheIt) && $cacheIt) {
				$this->cache->key = $this->shorturl;
				$this->cache->data = $this->url;
				$this->cache->type = "redirect";
				$this->cache->insert();
				$this->cache->type = "log";
			}

			return TRUE;
		}

	}


}

?>
