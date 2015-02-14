<?php
/**
 * Azizut API (core) class, design to be fast
 * @author Alex Alouit <alexandre.alouit@gmail.com>
 */

/*
 * Class autoloader
 */
function __autoload($classname) {
	$filename = "class." . $classname . ".php";
	include_once($filename);
}

class api {
	private $request = NULL;
	private $statusCode = 200;
	private $return = "";
	private $host = NULL;
	private $domain = NULL;
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
	private $cache = NULL;

	/*
	 * Constructor, dispatcher (build the way)
	 */
	public function __construct() {
		require_once './config/config.php';

		$this->timestamp = time();
		$this->ip = $_SERVER['REMOTE_ADDR'];
		$this->domain =  $_SERVER['HTTP_HOST'] . "/";
		if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == "on") {
			$this->host = "https://" . $this->domain;
		} else {
			$this->host = "http://" . $this->domain;
		}

		$data = file_get_contents("php://input");
		if(empty($data) OR !$this->request = json_decode($data)) {
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

		if(!empty($this->request->params->shorturl)) {
			$shorturl = $this->request->params->shorturl;
			$shorturl = str_replace(array($this->host, $this->domain, "/", ".qr"), "", $shorturl);
			if(preg_match("(\A[0-9a-zA-Z]{5}\z)", $shorturl)) {
				$this->shorturl = $shorturl;
			}
		}

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
	 * @return: json data for API users
	 */
	public function __destruct() {
		$this->return->statusCode = $this->statusCode;
		// return all data?
		// print json_encode($this);
		print json_encode($this->return);

		exit;
	}


	/*
	 * Insert a link in db and cache
	 * @return: (bool)
	 * @apiReturn: (string) shorturl
	 */
	private function insert() {
		if(!empty($this->request->params->url)) {
			$this->url = filter_var($this->request->params->url, FILTER_SANITIZE_URL);
			$this->giveMeShortUrl();
			$this->giveMeDescription();
			$result = $this->db->insertRow("INSERT INTO `data` (`shorturl`, `url`, `ip`, `description`, `owner`, `timestamp`) VALUES (?, ?, ?, ?, ?, ?) ;", 
			array($this->shorturl, $this->url, $this->ip, $this->description, $this->username, $this->timestamp));
			$this->return->data->shorturl = $this->shorturl;
			$this->return->data->link = $this->host . $this->shorturl;
			$this->return->data->description = $this->description;
			if($result) {
				if(CACHE) {
					$this->cache = new cache();
					$this->cache->type = "redirect";
					$this->cache->key = $this->shorturl;
					$this->cache->data = $this->url;
					if(!$this->cache->insert()) {
						error_log("Error, I failed to insert a link in the cache.");
					}
				}

				return TRUE;
			} else {

			$this->statusCode = 202;
			exit;
			}
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
		if(!empty($this->request->params->url)) {
			$this->url = $this->request->params->url;
		}

		if(isset($this->request->params->period) && 
			is_int($this->request->params->period->start) && 
			is_int($this->request->params->period->end)) {
			// valid period format
			$periodStart = $this->request->params->period->start;
			$periodEnd = $this->request->params->period->end;
		} else {
			// if period isn't set (or is invalid), use current year as reference
			$periodStart = strtotime(date("Y") . "-01-01 00:00:00");
			$periodEnd = strtotime(date("Y")+1 . "-01-01 00:00:00");
		}

		if(empty($this->shorturl) && empty($this->url)) {
// TODO: ADD FILTER LIKE TOPCLICKED, TIMESTAMP..

			if(!empty($this->request->params->start) AND is_int($this->request->params->start)) {
				$this->start = $this->request->params->start;
			}
			if(!empty($this->request->params->limit) AND is_int($this->request->params->limit)) {
				$this->limit = $this->request->params->limit;
			}
			$limiter = $this->start . ", " . $this->limit;
			$result = $this->db->getRows("SELECT * FROM `data` WHERE `owner` = ? ORDER BY `timestamp` DESC LIMIT ? , ? ;", 
			array($this->username, $this->start, $this->limit));

			if($result) {
				foreach($result as &$row) {
					$row->link = $this->host . $row->shorturl;
				}

				$this->return->data = $result;

				// statisctics extrapolation for all links
				if(isset($this->request->params->stats) && $this->request->params->stats) {
					$per_monthResult = $this->db->getRows("SELECT COUNT(`stats`.`shorturl`) AS `value`, MONTH(`stats`.`timestamp`) AS `month` FROM `stats` 
						LEFT JOIN `data` ON `stats`.`shorturl` = `data`.`shorturl` WHERE `data`.`owner` = ? AND `stats`.`timestamp` BETWEEN ? AND ? 
						GROUP BY YEAR(`stats`.`timestamp`), MONTH(`stats`.`timestamp`) ;",
						array($this->username, $periodStart, $periodEnd));
					foreach($per_monthResult as $row) {
						$this->return->data["stats"]->per_month[$row->month] = $row->value;
					}

					$per_dayOfWeekResult = $this->db->getRows("SELECT COUNT(`stats`.`shorturl`) AS `value`, DAYOFWEEK(`stats`.`timestamp`) AS `dayOfWeek` FROM `stats` 
						LEFT JOIN `data` ON `stats`.`shorturl` = `data`.`shorturl` WHERE `data`.`owner` = ? AND `stats`.`timestamp` BETWEEN ? AND ? 
						GROUP BY DAYOFWEEK(`stats`.`timestamp`) ;",
						array($this->username, $periodStart, $periodEnd));
					foreach($per_dayOfWeekResult as $row) {
						$this->return->data["stats"]->per_dayOfWeek[$row->dayOfWeek] = $row->value;
					}

					$per_dayResult = $this->db->getRows("SELECT COUNT(`stats`.`shorturl`) AS `value`, DAY(`stats`.`timestamp`) AS `day` FROM `stats` 
						LEFT JOIN `data` ON `stats`.`shorturl` = `data`.`shorturl` WHERE `data`.`owner` = ? AND `stats`.`timestamp` BETWEEN ? AND ? 
						GROUP BY YEAR(`stats`.`timestamp`), MONTH(`stats`.`timestamp`), DAY(`stats`.`timestamp`) ;",
						array($this->username, $periodStart, $periodEnd));
					foreach($per_dayResult as $row) {
						$this->return->data["stats"]->per_day[$row->day] = $row->value;
					}

					$per_hourResult = $this->db->getRows("SELECT COUNT(`stats`.`shorturl`) AS `value`, HOUR(`stats`.`timestamp`) AS `hour` FROM `stats` 
						LEFT JOIN `data` ON `stats`.`shorturl` = `data`.`shorturl` WHERE `data`.`owner` = ? AND `stats`.`timestamp` BETWEEN ? AND ? 
						GROUP BY YEAR(`stats`.`timestamp`), MONTH(`stats`.`timestamp`), DAY(`stats`.`timestamp`), HOUR(`stats`.`timestamp`) ;",
						array($this->username, $periodStart, $periodEnd));
					foreach($per_hourResult as $row) {
						$this->return->data["stats"]->per_hour[$row->hour] = $row->value;
					}
// TODO: (bug) we have result as last entry on sub-sub object pointer ($this->return->data)

					$this->return->data["stats"]->per_referer = $this->db->getRows("SELECT COUNT(`stats`.`shorturl`) AS `value`, `stats`.`timestamp` FROM `stats` 
						LEFT JOIN `data` ON `stats`.`shorturl` = `data`.`shorturl` WHERE `data`.`owner` = ? AND `stats`.`timestamp` BETWEEN ? AND ? 
						GROUP BY `stats`.`referer` ;",
						array($this->username, $periodStart, $periodEnd));

					$this->return->data["stats"]->per_useragent = $this->db->getRows("SELECT COUNT(`stats`.`shorturl`) AS `value`, `stats`.`timestamp` FROM `stats` 
						LEFT JOIN `data` ON `stats`.`shorturl` = `data`.`shorturl` WHERE `data`.`owner` = ? AND `stats`.`timestamp` BETWEEN ? AND ? 
						GROUP BY `stats`.`useragent` ;",
						array($this->username, $periodStart, $periodEnd));
				}

				return TRUE;
			} else {

				$this->statusCode = 404;
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


			if($result) {
				$this->hydrate($result);
				$this->return->data = $result;
				$this->return->data->link = $this->host . $result->shorturl;

				// statisctics extrapolation for one link
				if(isset($this->request->params->stats) && $this->request->params->stats && isset($this->shorturl)) {
					$per_monthResult = $this->db->getRows("SELECT COUNT(`shorturl`) AS `value`, MONTH(`timestamp`) AS `month` FROM `stats` 
						WHERE `shorturl`= ? AND `timestamp` BETWEEN ? AND ? 
						GROUP BY YEAR(`timestamp`), MONTH(`timestamp`) ;",
						array($this->shorturl, $periodStart, $periodEnd));
					foreach($per_monthResult as $row) {
						$this->return->data->stats->per_month[$row->month] = $row->value;
					}

					$per_dayOfWeekResult = $this->db->getRows("SELECT COUNT(`shorturl`) AS `value`, DAYOFWEEK(`timestamp`) AS `dayOfWeek` FROM `stats` 
						WHERE `shorturl`= ? AND `timestamp` BETWEEN ? AND ? 
						GROUP BY DAYOFWEEK(`timestamp`) ;",
						array($this->shorturl, $periodStart, $periodEnd));
					foreach($per_dayOfWeekResult as $row) {
						$this->return->data->stats->per_dayOfWeek[$row->dayOfWeek] = $row->value;
					}

					$per_dayResult = $this->db->getRows("SELECT COUNT(`shorturl`) AS `value`, DAY(`timestamp`) AS `day` FROM `stats` 
						WHERE `shorturl`= ? AND `timestamp` BETWEEN ? AND ? 
						GROUP BY YEAR(`timestamp`), MONTH(`timestamp`), DAY(`timestamp`) ;",
						array($this->shorturl, $periodStart, $periodEnd));
					foreach($per_dayResult as $row) {
						$this->return->data->stats->per_day[$row->day] = $row->value;
					}

					$per_hourResult = $this->db->getRows("SELECT COUNT(`shorturl`) AS `value`, HOUR(`timestamp`) AS `hour` FROM `stats` 
						WHERE `shorturl`= ? AND `timestamp` BETWEEN ? AND ? 
						GROUP BY YEAR(`timestamp`), MONTH(`timestamp`), DAY(`timestamp`), HOUR(`timestamp`) ;",
						array($this->shorturl, $periodStart, $periodEnd));
					foreach($per_hourResult as $row) {
						$this->return->data->stats->per_hour[$row->hour] = $row->value;
					}

					$this->return->data->stats->per_referer = $this->db->getRows("SELECT COUNT(`shorturl`) AS `value`, `timestamp`, `referer` FROM `stats` 
						WHERE `shorturl`= ? AND `timestamp` BETWEEN ? AND ? GROUP BY `referer` ;",
						array($this->shorturl, $periodStart, $periodEnd));

					$this->return->data->stats->per_useragent = $this->db->getRows("SELECT COUNT(`shorturl`) AS `value`, `timestamp`, `useragent` FROM `stats` 
						WHERE `shorturl`= ? AND `timestamp` BETWEEN ? AND ? GROUP BY `useragent` ;",
						array($this->shorturl, $periodStart, $periodEnd));
				}

				return TRUE;
			} else {

				$this->statusCode = 404;
				return FALSE;
			}
		} else {

			$this->statusCode = 400;

			exit;
		}

	}

	/*
	 * Update a link in db and cache
	 * @return: (bool)
	 */
	private function update() {
		if(empty($this->shorturl) && empty($this->request->params->url)) {

			$this->statusCode = 405;
			exit;
		}

		if($this->get()) {
			$currentShorturl = $this->shorturl;
			if((!empty($this->request->params->newUrl) && $this->request->params->newUrl != $this->url) OR 
			(!empty($this->request->params->newShorturl) && $this->request->params->newShorturl != $this->shorturl OR 
			isset($this->request->params->newShorturl) && empty($this->request->params->newShorturl))) {
				if(isset($this->request->params->newShorturl) && empty($this->request->params->newShorturl)) {
					$this->giveMeShortUrl();
				} elseif(isset($this->request->params->newShorturl) && $this->request->params->newShorturl != $this->shorturl) {
					if(!$this->isPresent($this->request->params->newShorturl)) {
						$this->shorturl = $this->request->params->newShorturl;
					} else {

						$this->statusCode = 403;
						exit;
					}
				}

				if(!empty($this->request->params->newUrl) && $this->request->params->newUrl != $this->url) {
					$this->url = $this->request->params->newUrl;
					$this->giveMeDescription();
				}

				$result = $this->db->updateRow("UPDATE `data` SET `url` = ?, `ip` = ?, `description` = ?, `shorturl` = ? WHERE `shorturl` = ? AND `owner` = ? ;", 
				array($this->url, $this->ip, $this->description, $this->shorturl, $currentShorturl, $this->username));

				if($result) {
					if(CACHE) {
						$this->cache = new cache();
						$this->cache->type = "redirect";
						$this->cache->key = $currentShorturl;
						if($this->cache->get()) {
							if(!$this->cache->delete()) {
								error_log("I failed to delete a link in the cache.");
							}
						}
						$this->cache->key = $this->shorturl;
						$this->cache->data = $this->url;
						if(!$this->cache->insert()) {
							error_log("I failed to insert a link in the cache.");
						}
					}

					if($this->shorturl != $currentShorturl) {
						$result = $this->db->updateRow("UPDATE `stats` SET `shorturl` = ? WHERE `shorturl` = ? ;", 
							array($this->shorturl, $currentShorturl));
					}
				}

				$this->return->data->url = $this->url;
				$this->return->data->shorturl = $this->shorturl;
				$this->return->data->link = $this->host . $this->shorturl;
				$this->return->data->description = $this->description;
// TODO: FIX THIS, DON'T RETURN BOOL? ->
				if($result) {

					return TRUE;
				} else {
	
					$this->statusCode = 202;
					exit;
				}
			} else {

				$this->statusCode = 405;
				exit;
			}
		} else {

			$this->statusCode = 404;
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
	 * Delete an shorturl, and it's stats from db and cache
	 * @return (bool)
	 */
	private function delete() {
		if(empty($this->shorturl) && empty($this->request->params->url)) {

			$this->statusCode = 405;
			exit;
		}

		if($this->get()) {
			if(CACHE) {
				$this->cache = new cache();
				$this->cache->type = "redirect";
				$this->cache->key = $this->shorturl;
				if($this->cache->get()) {
					if(!$this->cache->delete()) {
						error_log("I failed to delete a link in the cache.");
					}
				}
			}

			// Data is gold, we keep it!
			$result1 = $this->db->insertRow("INSERT INTO `data_deleted` SELECT * FROM data WHERE `shorturl` = ? AND `owner` = ? ;", array($this->shorturl, $this->username));
			$result2 = $this->db->deleteRow("DELETE FROM `data` WHERE `shorturl` = ? AND `owner` = ? ;", array($this->shorturl, $this->username));
// TODO: WE NEED TO PREVENT STRONG QUERY, USE CACHE+CRON FOR ASYNC QUERIES
			$result3 = $this->db->insertRow("INSERT INTO `stats_deleted` SELECT * FROM stats WHERE `shorturl` = ? ;", array($this->shorturl));
			$result4 = $this->db->deleteRow("DELETE FROM `stats` WHERE `shorturl` = ? ;", array($this->shorturl));
			if($result1 && $result2 && $result3 && $result4) {
				$this->statusCode = 200;
				return TRUE;
			} else {
				$this->statusCode = 202;
				return FALSE;
			}

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
			$this->db = new db(MYSQL_SERVER, MYSQL_DATABASE, MYSQL_USER, MYSQL_PASSWORD);
			$result = $this->db->getRow("SELECT COUNT(*) FROM `auth` WHERE `username` = ? AND `password` = ? ;", 
			array($this->request->access->username, $this->request->access->password));

			if($result->{"COUNT(*)"} == 1) {
				$this->username = $this->request->access->username;
				$this->password = $this->request->access->password;

				return TRUE;
			} else {

				return FALSE;
			}
		}

	}

	/*
	 * Check a shorturl is present or not
	 * (use first argument, otherwise $this->shorturl is use)
	 * @params: (string) shorturl
	 * @return: (bool)
	 */
	private function isPresent($shorturl = NULL) {
		if(empty($shorturl)) {
			$shorturl = $this->shorturl;
		}

// TODO: add cache
		$result = $this->db->getRow("SELECT COUNT(*) FROM `data` WHERE `shorturl` = ? ;", array($shorturl));

		// loop until is unique
		if($result->{"COUNT(*)"} == 1) {

			return TRUE;
		} else {

			return FALSE;
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
		if($this->isPresent($shorten)) {
			// loop until is unique
			$this->giveMeShortUrl();
		} else {
			$this->shorturl = $shorten;

			return TRUE;
		}
	}

	/*
	 * Check url is valid, and update description
	 * @return (bool)
	 */
	private function giveMeDescription() {
		if(!empty($this->url)) {
// TODO: check return header, and check status code (site is valid?)
			$buffer = file_get_contents($this->url);
			if(strlen($buffer) > 0){
				preg_match("/\<title\>(.*)\<\/title\>/", $buffer, $description);
// TODO: fix this dummy things->
				if(!empty($description[0])) {
					$this->description = substr($description[0], 7, -8);
				}

				return TRUE;
			} else {

				return FALSE;
			}
		}
	}



}

?>
