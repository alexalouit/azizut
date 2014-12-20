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
	/*
	 * Cache key
	 */
	public $key = NULL;
	/*
	 * Cache data
	 */
	public $data = NULL;

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
	}

	/*
	 * Destructor
	 */
	public function __destruct() {
	}

	/*
	 * Get data from cache
	 * @return: (bool)/data
	 */
	public function get() {
		switch(CACHE_TYPE) {
			case "apc":
			break;
			case "memcached":
			break;
		}
	}

	/*
	 * Get asynchronous job for cron
	 * @return: (bool)/data
	 */
	public function getAsync() {
		foreach($i <= QPP) {
			switch(CACHE_TYPE) {
				case "apc":
				break;
				case "memcached":
				break;
			}
		}
	}

	/*
	 * Insert an asynchronous job for cron
	 * @return: (bool)
	 */
	public function insertAsync() {
		switch(CACHE_TYPE) {
			case "apc":
			break;
			case "memcached":
			break;
		}
	}

	/*
	 * Insert link in cache
	 * @return: (bool)
	 */
	public function insert() {
		switch(CACHE_TYPE) {
			case "apc":
			break;
			case "memcached":
			break;
		}
	}

	/*
	 * Delete link from cache
	 * @return: (bool)
	 */
	public function delete() {
		switch(CACHE_TYPE) {
			case "apc":
			break;
			case "memcached":
			break;
		}
	}


}
?>