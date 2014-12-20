<?php
// MySQL
define("MYSQL_SERVER", "127.0.0.1");
define("MYSQL_DATABASE", "");
define("MYSQL_USER", "");
define("MYSQL_PASSWORD", "");
// Cache
define("ASYNC", FALSE); // TRUE/FALSE (work only witch cache type defined, require cron)
define("QPP", 50); // Maximum  queries per packet (when async mode available)
define("CACHE_TYPE", NULL); // NULL/apc/memcached
// Memcached
define("MEMCACHED_SERVER", "127.0.0.1");

?>
