<?php
/**
 * Azizut API (core) class, design to be fast
 * @author Alex Alouit <alexandre.alouit@gmail.com>
 */

class api {

	private $request = NULL;
	private $statusCode = 200;
	private $guest = TRUE;
	private $return = "";
	private $host = NULL;
	private $headers = NULL;
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

	/*
	 * Constructor, dispatcher (build the way)
	 */
	public function __construct() {
// TODO: DISPACT REQUIRED FILES CORRECTLY FOR FASTER PROCESS
//		require_once $_SERVER['DOCUMENT_ROOT'].'/classes/class.cache.php';
		require_once dirname( __FILE__ ) . '/../config/config.php';

		$this->timestamp = date("o-m-d H:i:s");

		$data = file_get_contents("php://input");
		if(empty($data)) {
			$this->serve();
		}

		$this->guest = FALSE;

		if(!$this->request = json_decode($data)) {
			$this->statusCode = 400;

			exit;
		}

		if(!$this->auth()) {
			// generate error for log sniffer like fail2ban
			// ip and date are already formated
			error_log("user {$this->request->access->username}: authentification failure for Azizut API: Password Mismatch");
			$this->statusCode = 401;

			exit;
		}

		if(empty($this->request->action)) {
			$this->statusCode = 400;
			exit;
		}

		$this->ip = $_SERVER['REMOTE_ADDR'];

		switch($this->request->action) {
			case "insert":
				$this->insert();
				break;
			case "get":
				$this->get();
				break;
			case "update":
				$this->update();
				break;
			case "delete":
				$this->delete();
				break;
			default:
				$this->statusCode = 400;

				exit;
				break;
		}
	}

	/*
	 * Desctructor, return the content
	 * @return: http header for guests, json data for API users
	 */
	public function __destruct() {
		if(!$this->guest) {
			$this->return->statusCode = $this->statusCode;
			print json_encode($this->return);
		} else {
			header("HTTP/1.1 301 Moved Permanently");
			header("Location: {$this->return}");
		}

		exit;
	}

	private function serve() {
		$this->shorturl = $_SERVER['REQUEST_URI'];
		$this->shorturl = substr($this->shorturl, 1);
		if(substr($this->shorturl, -3) == ".qr") {
			$qrcode = TRUE;
			$this->shorturl = substr($this->shorturl, 0, -3);
		}

		require_once dirname( __FILE__ ) . '/class.db.php';
		$this->db = new db(MYSQL_SERVER, MYSQL_DATABASE, MYSQL_USER, MYSQL_PASSWORD);

		if(!$this->get()) {

			$this->return = "/error/404.html";

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

			$this->log();
			$this->return = $this->url;

			exit;

		}

	}

	/*
	 * QRCode returner
	 * @return: (bool)
	 * @apiReturn: (string) qrcode link
	 */
	private function qr() {
		if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == "on") {
			$this->host = "https://" . $_SERVER['HTTP_HOST'] . "/";
		} else {
			$this->host = "http://" . $_SERVER['HTTP_HOST'] . "/";
		}

		$this->return = "http://chart.apis.google.com/chart?chs=150x150&cht=qr&chld=M&chl=" . $this->host . $this->shorturl;
		exit;
	}

// TODO: CREATE CRON, AND STOCK LOG IN CACHE (IF IS POSSIBLE) WITH INDEX
	/*
	 * Log an guest for stats
	 * @return (bool)
	 */
	private function log() {
		$result = $this->db->insertRow("INSERT INTO `stats` (`shorturl`, `ip`, `useragent`, `referer`, `timestamp`) VALUES (?, ?, ?, ?, ?) ;", 
		array($this->shorturl, $this->ip, $this->useragent, $this->referer, $this->timestamp));
// TODO: WE NEED TO CHECK RETURN AND RETURN BOOL STATUS
// TODO: UPDATE CLICK INT FIELD IN DATA TABLE
	}

	/*
	 * Insert a link
	 * @return: (bool)
	 * @apiReturn: (string) shorturl
	 */
	private function insert() {
		if(!empty($this->request->params->url)) {
			$this->url = filter_var($this->request->params->url, FILTER_SANITIZE_URL);
			$this->giveMeShortUrl();
			$result = $this->db->insertRow("INSERT INTO `data` (`shorturl`, `url`, `ip`, `description`, `owner`, `timestamp`) VALUES (?, ?, ?, ?, ?, ?) ;", 
			array($this->shorturl, $this->url, $this->ip, $this->description, $this->username, $this->timestamp));
// TODO: WE NEED TO CHECK RETURN AND RETURN BOOL STATUS
// if ok->
			$this->return->data = $this->shorturl;
		} else {

			$this->statusCode = 400;

			exit;
		}
	}

	/*
	 * If no shorturl/url fetch multiple links
	 * else use shorturl or long url (short as faster)
	 * can use filter: (pagination: start & limit)
	 * if stats(bool) defined, return complete statistics
	 * @return (bool)
	 * @apiReturn: (object) result(s)
	 */
	private function get() {

		// only use by guests
// TODO: SEPARATE FUNCTION
		if($this->guest) {
				$result = $this->db->getRow("SELECT * FROM `data` WHERE `shorturl` = ? ;", 
				array($this->shorturl));

			if($result && !empty($result->url)) {
				$this->url = $result->url;
				$this->return->data = $result;

				return TRUE;
			} else {

				return FALSE;
			}
		}

		// use by api
		if(!empty($this->request->params->shorturl)) {
			$this->shorturl = $this->request->params->shorturl;
		}
		if(!empty($this->request->params->url)) {
			$this->url = $this->request->params->url;
		}

		if(empty($this->shorturl) && empty($this->url)) {
// TODO: ADD FILTER LIKE TOPCLICKED, TIMESTAMP..

			if(!empty($this->request->params->start) AND is_int($this->request->params->start)) {
				$this->start = $his->request->params->start;
			}
			if(!empty($this->request->params->limit) AND is_int($this->request->params->limit)) {
				$this->limit = $his->request->params->limit;
			}
// TODO: FIX LIMIT, DON'T WORK
			$result = $this->db->getRows("SELECT * FROM `data` WHERE `owner` = ? LIMIT ? , ? ;", 
			array($this->username, $this->start, $this->limit));

			if($result) {
				$this->return->data = $result;

				return TRUE;
			} else {

				return FALSE;
			}

		} elseif(!empty($this->shorturl) OR !empty($this->url)) {
			if(!empty($this->shorturl)) {
				$result = $this->db->getRow("SELECT * FROM `data` WHERE `shorturl` = ? AND `owner` = ? ;", 
				array($this->shorturl, $this->username));
			} elseif(!empty($this->url)) {
				$result = $this->db->getRow("SELECT * FROM `data` WHERE `url` = ? AND `owner` = ? ;", 
				array($this->url, $this->username));
			}

			if(isset($this->request->params->stats) && !$this->request->params->stats) {
// TODO: CATCH ALL SHORTURL STATS, AND RETURN IT FORMATED FOR EXTRAPOLATION
			}

			if($result) {
				$this->hydrate($result);
				$this->return->data = $result;

				return TRUE;
			} else {

				return FALSE;
			}
		} else {

			$this->statusCode = 400;

			exit;

		}

	}

	/*
	 * Update a link
	 * @return: (bool)
	 */
	private function update() {
	$result = $this->get();
// TODO: WE NEED TO CHECK WE HAVE RESULT
	if($result) {
		$this->hydrate($result);
// TODO: WE NEED TO CHECK DESCRIPTION
		$result = $this->db->updateRow("UPDATE `data` SET `url` = ?, `ip` = ?, `description` = ? WHERE `shorturl` = ? AND `owner` = ? ;", 
		array($this->url, $this->ip, $this->description, $this->shorturl, $this->user));
// TODO: WE NEED TO CHECK RESULT
	} else {

			$this->statusCode = 405;

			exit;

		}

	}

	/*
	 * Hydrate function, update data
	 */
	private function hydrate($data) {
		if(!empty($data)) {
			foreach($data as $key => $property) {
				if(property_exists($this, $key)) {
					$this->{$key} = $property;
				}
			}
		}
	}

	/*
	 * Delete an shortul, and it's stats
	 * @return (bool)
	 */
	private function delete() {
		if(!empty($this->request->params->shorturl)) {
			$result[] = $this->db->insertRow("INSERT INTO `data_deleted` SELECT * FROM data WHERE `shorturl` = ? AND `owner` = ? ;", array($this->request->params->shorturl, $this->username));
			$result[] = $this->db->deleteRow("DELETE FROM `data` WHERE `shorturl` = ? AND `owner` = ? ;", array($this->request->params->shorturl, $this->username));
// TODO: WE NEED TO CHECK RESULT FOR VALIDATE IS THE OWNER,
// TODO: WE NEED TO PREVENT STRONG QUERY, USE CACHE+CRON FOR ASYNC QUERIES
//			$result[] = $this->db->insertRow("INSERT INTO `stats_deleted` SELECT * FROM stats WHERE `shorturl` = ? ;", array($this->request->params->shorturl));
//			$result[] = $this->db->deleteRow("DELETE FROM `stats` WHERE `shorturl` = ? ;", array($this->request->params->shorturl));
			// Data is gold, we keep it!
		} elseif(!empty($this->request->params->url)) {
			$result[] = $this->db->insertRow("INSERT INTO `data_deleted` SELECT * FROM data WHERE `url` = ? AND `owner` = ? ;", array($this->request->params->url, $this->username));
			$result[] = $this->db->deleteRow("DELETE FROM `data` WHERE `url` = ? AND `owner` = ? ;", array($this->request->params->url, $this->username));
// TODO: WE NEED TO CHECK RESULT FOR VALIDATE IS THE OWNER,
// TODO: WE NEED TO PREVENT STRONG QUERY, USE CACHE+CRON FOR ASYNC QUERIES
//			$result[] = $this->db->insertRow("INSERT INTO `stats_deleted` SELECT * FROM stats WHERE `url` = ? ;", array($this->request->params->url));
//			$result[] = $this->db->deleteRow("DELETE FROM `stats` WHERE `url` = ? ;", array($this->request->params->url));
		} else {

			$this->statusCode = 400;

			exit;
		}

	}

	/*
	 * Determine if user is valid
	 * @return (bool)
	 */
	private function auth() {
		if(empty($this->request->access->username) OR empty($this->request->access->password)) {

			return FALSE;
		} else {
			require_once dirname( __FILE__ ) . '/class.db.php';
			$this->db = new db(MYSQL_SERVER, MYSQL_DATABASE, MYSQL_USER, MYSQL_PASSWORD);
			$result = $this->db->getRow("SELECT COUNT(*) FROM `auth` WHERE `username` = ? AND `password` = ? ;", 
			array($this->request->access->username, $this->request->access->password));
			if($result->{"COUNT(*)"} === "1") {
				$this->username = $this->request->access->username;
				$this->password = $this->request->access->password;

				return TRUE;
			} else {
				return FALSE;
			}
		}

	}

	/*
	 * Generate a unique shorturl
	 * @return (bool)
	 */
	private function giveMeShortUrl() {
		$pattern = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRST";
		$shorten = "";
		for($i = 0; $i < 5; $i++) {
			$data = rand(0, strlen($pattern) - 1);
			$shorten .= substr($pattern, $data, 1);
		}

		// check is unique
		$result = $this->db->getRow("SELECT COUNT(*) FROM `data` WHERE `shorturl` = ? ;", array($shorten));

		// loop until is unique
		if($result->{"COUNT(*)"} === "1") {
			$this->giveMeShortUrl();
		} else {
			$this->shorturl = $shorten;
		
			return TRUE;
		}

	}



}

?>
