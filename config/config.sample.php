<?php
// MySQL
define("MYSQL_SERVER", "127.0.0.1");
define("MYSQL_DATABASE", "");
define("MYSQL_USER", "");
define("MYSQL_PASSWORD", "");
// Cache
define("ASYNC", FALSE); // TRUE/FALSE (work only witch cache type defined, require cron)
define("QPP", 50); // Maximum  queries per packet (when async mode available)
define("CACHE", TRUE); // TRUE/FALSE
define("MEMCACHED_SERVER", "127.0.0.1");
define("MEMCACHED_PORT", 11211);
define("MEMCACHED_TTL", 259200); // Cache expiration in seconds, default to 3 days (259200)
// DEBUG
define("DEBUG", "debug.log"); // path file for debugging, /dev/null for disable
?>
