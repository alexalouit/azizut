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
	private $host = NULL;
	private $ip = "unknown";
	private $clicks = 0;
	private $uniquid = NULL;
	private $timestamp = NULL;
	private $referer = "unknown";
	private $useragent = "unknown";
	private $url = "";
	private $shorturl = NULL;
	private $start = 0;
	private $limit = 500;
	private $db = NULL;
	private $cache = NULL;
	private $processTime = 0;
	private $cacheHit = 1;
	private $qrcode = FALSE;

	/*
	 * Constructor, dispatcher (build the way)
	 */
	public function __construct() {
		$this->processTime = microtime(TRUE);
		require_once './config/config.php';

		$this->timestamp = date("Y-m-d H:i:s");
		$this->ip = $_SERVER['REMOTE_ADDR'];

		$this->shorturl = $_SERVER['REQUEST_URI'];

		$this->shorturl = substr($this->shorturl, 1);
		if(substr($this->shorturl, -3) == ".qr") {
			$this->qrcode = TRUE;
			$this->shorturl = substr($this->shorturl, 0, -3);
		}

		$this->shorturl = str_replace("/", "", $this->shorturl);
		if(!preg_match("(\A[0-9a-zA-Z]{5}\z)", $this->shorturl) && !empty($this->shorturl)) {
			exit;
		}

		if(CACHE) {
			$this->cache = new cache();
		}

		if(!$this->get()) {
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

			if(isset($this->qrcode) && $this->qrcode) {
				$this->domain =  $_SERVER['HTTP_HOST'] . "/";
				if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == "on") {
					$this->host = "https://" . $this->domain;
				} else {
					$this->host = "http://" . $this->domain;
				}

				$this->qr();
			}

			exit;
		}
	}

	/*
	 * Desctructor, return the content
	 * @return: http header for guests
	 */
	public function __destruct() {
		if(empty($this->url)) {
			error_log("File does not exist: /" . $this->shorturl);
			header("HTTP/1.1 404 Not Found");
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
	<head>
		<title>ERROR 404 - Not Found!</title>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<meta name="robots" content="noindex" />
	</head>
	<body>
		<h1>ERROR 404 - Not Found!</h1>
		<h2>The following error occurred:</h2>
		<p>The requested URL was not found on this server.</p>
	</body>
</html>
<?php
//			$this->log();
			exit;
		} else {
			$this->log();
			header("HTTP/1.1 301 Moved Permanently");
			header("Location: {$this->url}");
		}
		exit;
	}

	/*
	 * QRCode returner
	 * @return: (bool)
	 * @apiReturn: (string) qrcode link
	 */
	private function qr() {
		$this->url = "http://chart.apis.google.com/chart?chs=150x150&cht=qr&chld=M&chl=" . $this->host . $this->shorturl;

		exit;
	}

	/*
	 * Log an guest for stats
	 * @return (bool)
	 */
	private function log() {
// TODO: return correct (bool) value
		$this->processTime = (microtime(TRUE) - $this->processTime);
		if(CACHE && ASYNC) {
			$this->cache->type = "log";
			$this->cache->data = new stdClass;
			$this->cache->data->shorturl = $this->shorturl;
			$this->cache->data->ip = $this->ip;
			$this->cache->data->useragent = $this->useragent;
			$this->cache->data->referer = $this->referer;
			$this->cache->data->timestamp = $this->timestamp;
			$this->cache->data->processTime = $this->processTime;
			$this->cache->data->cacheHit = $this->cacheHit;
			if(!$this->cache->insert()) {
				error_log("Error with cache, insert directly in DB.");
				$this->db = new db(MYSQL_SERVER, MYSQL_DATABASE, MYSQL_USER, MYSQL_PASSWORD);
				$result = $this->db->insertRow("INSERT INTO `stats` (`shorturl`, `ip`, `useragent`, `referer`, `timestamp`, `processtime`, `cacheHit`) VALUES (?, ?, ?, ?, ?, ?, ?) ;", 
					array($this->shorturl, $this->ip, $this->useragent, $this->referer, $this->timestamp, $this->processTime, $this->cacheHit));
// TODO: WE NEED TO CHECK RETURN AND RETURN BOOL STATUS
				$result = $this->db->insertRow("UPDATE `data` SET `clicks` = `clicks` + 1 WHERE `shorturl` = ? ;", 
					array($this->shorturl));
			}
		} else {
			$this->db = new db(MYSQL_SERVER, MYSQL_DATABASE, MYSQL_USER, MYSQL_PASSWORD);
			$result = $this->db->insertRow("INSERT INTO `stats` (`shorturl`, `ip`, `useragent`, `referer`, `timestamp`, `processtime`, `cacheHit`) VALUES (?, ?, ?, ?, ?, ?, ?) ;", 
				array($this->shorturl, $this->ip, $this->useragent, $this->referer, $this->timestamp, $this->processTime, $this->cacheHit));
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
			$this->url = $this->cache->get();
			if(is_bool($this->url)) {
				// error with cache
				$this->cacheHit = 0;
				$this->db = new db(MYSQL_SERVER, MYSQL_DATABASE, MYSQL_USER, MYSQL_PASSWORD);
				$result = $this->db->getRow("SELECT * FROM `data` WHERE `shorturl` = ? ;", 
					array($this->shorturl));

				$this->url = $result->url;
				$this->cache->data = $this->url;
				@error_log("/" . $this->shorturl . " to " . $this->url . " INSERT-CACHE\n", 3, DEBUG);
				$this->cache->insert();
				if(!empty($this->url)) {
//					@error_log("/" . $this->shorturl . " to " . $this->url . " HIT-DB\n", 3, DEBUG);

					return TRUE;
				} else {
					@error_log("/" . $this->shorturl . " MISS-DB\n", 3, DEBUG);


					return FALSE;
				}
			} else {
				$this->cacheHit = 1;

				return TRUE;
			}

		} else {
			$this->cacheHit = 0;
			$this->db = new db(MYSQL_SERVER, MYSQL_DATABASE, MYSQL_USER, MYSQL_PASSWORD);
			$result = $this->db->getRow("SELECT * FROM `data` WHERE `shorturl` = ? ;", 
				array($this->shorturl));

			if(empty($result->url)) {
				@error_log("/" . $this->shorturl . " MISS-DB\n", 3, DEBUG);

				return FALSE;
			} else {
//				@error_log("/" . $this->shorturl . " to " . $result->url . " HIT-DB\n", 3, DEBUG);
				$this->url = $result->url;

				return TRUE;
			}
		}
	}


}

?>
