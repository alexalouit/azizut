<?php
/**
 * Cache classes, support APC and Memcached.
 * @author Alex Alouit <alexandre.alouit@gmail.com>
 */

class cache {
	/*
	 * Cache expiration, default to 3 days
	 */
	private $ttl = 259200;
	private $server = "localhost";
	private $port = 11211;
	private $qpp = 50;
	private $index = array();
	private $memcached = FALSE;
	private $salt = "azizut_";
	/*
	 * Cache key
	 */
	public $key = NULL;
	/*
	 * Cache data
	 */
	public $data = NULL;

	public $type = NULL;

	/*
	 * Constructor
	 */
	public function __construct() {
		if(!empty(MEMCACHED_SERVER)) {
			$this->server = MEMCACHED_SERVER;
		}
		if(!empty(MEMCACHED_PORT)) {
			$this->port = MEMCACHED_PORT;
		}
		if(!empty(QPP)) {
			$this->qpp = QPP;
		}
		if(!empty(MEMCACHED_TTL)) {
			$this->ttl = MEMCACHED_TTL;
		}

		$this->memcached = new Memcached();
		if(!$this->memcached->addServer($this->server, $this->port)) {
			return FALSE;
		}
	}

	/*
	 * Destructor
	 */
	public function __destruct() {
		$this->memcached->quit();
	}

	/*
	 * Get data from cache
	 * @return: (bool)/data
	 */
	public function get() {
		if($this->type == "log") {
			$return = array();
			$this->index = $this->memcached->get($this->salt . "index");
			$i = 1;
			error_log("Search index..");
			foreach($this->index as $key => &$value) {
				if($i >= $this->qpp) {
					break;
				}
				unset($this->index[$key]);
				$result = $this->memcached->get($this->salt . $value);
				if(!empty($result) && $result !== FALSE) {
					$return[$i] = $result;
				}
				$this->memcached->delete($this->salt . $value);
				$i++;
			}
			error_log("Update index..");
			if(empty($this->index)) {
				$this->index = array();
			}
			$this->memcached->set($this->salt . "index", $this->index, $this->ttl);
			return $return;
			
		} elseif($this->type == "redirect") {
			return  $this->memcached->get($this->salt . $this->key);
		}
	}

	/*
	 * Insert link in cache
	 * @return: (bool)
	 */
	public function insert() {
		if($this->type == "log") {
			$this->key = uniqid();
			$this->index = $this->memcached->get($this->salt . "index");
			$this->index[] = $this->key;
			if(!$this->memcached->set($this->salt . "index", $this->index, $this->ttl)) {
				error_log("Error cache when insert index.");
				return FALSE;
			} else {
				error_log("Insert index for as " . $this->key);
				if(!$this->memcached->set($this->salt . $this->key, $this->data, $this->ttl)) {
					error_log("Error cache when insert log.");
					return FALSE;
				} else {
					error_log("Insert log for " . $this->key);
					return TRUE;
				}
			}
		} elseif($this->type == "redirect") {
			error_log("Insert redirect " . $this->data . " from /" . $this->key);
			return $this->memcached->set($this->salt . $this->key, $this->data, $this->ttl);
		}
	}

	/*
	 * Delete link from cache
	 * @return: (bool)
	 */
	public function delete() {
		error_log("Delete redirect /" . $this->key);
		return $this->memcached->delete($this->salt . $this->key);
	}


}
?>