Web server check.
<?php
if(isset($_SERVER['SERVER_SOFTWARE']) && strpos($_SERVER['SERVER_SOFTWARE'], "pache") !== FALSE ) {
} else {
?>
<br />
<b>/!\ Web server unknown or not supported</b>
<?php
}

require_once dirname( __FILE__ ) . '/config/config.php';
?>
<br />
MySQL connection.
<?php
try {
	$bdd = new PDO('mysql:host='.MYSQL_SERVER.';dbname='.MYSQL_DATABASE.'', ''.MYSQL_USER.'', ''.MYSQL_PASSWORD.'');
} catch (Exception $e){ 
?>
<br />
<b>/!\ Impossible connection to MySQL db.</b>
<?php
print $e->getMessage();
}
?>
<br />
Create MySQL tables.
<br />



1/5 "users" 
<?php
$query = "DROP TABLE IF EXISTS `auth`; 
CREATE TABLE `auth` ( 
  `username` varchar(99) NOT NULL DEFAULT '', 
  `password` varchar(99) NOT NULL DEFAULT '', 
  UNIQUE KEY `username` (`username`) 
) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
if($req = $bdd->exec($query) !== FALSE) {
?>
OK
<?php
} else {
?>
Problem!
<?php
}
?>



<br />
2/5 "data" 
<?php
$query = "DROP TABLE IF EXISTS `data`; 
CREATE TABLE `data` ( 
  `shorturl` varchar(8) NOT NULL DEFAULT '', 
  `url` varchar(255) NOT NULL DEFAULT '', 
  `clicks` int(99) NOT NULL DEFAULT '0', 
  `ip` varchar(64) NOT NULL DEFAULT '', 
  `description` varchar(255) NOT NULL DEFAULT '', 
  `owner` varchar(99) NOT NULL DEFAULT '', 
  `timestamp` datetime NOT NULL, 
  UNIQUE KEY `shorturl` (`shorturl`), 
  KEY `owner` (`owner`) 
) ENGINE=InnoDB DEFAULT CHARSET=utf8; 
INSERT INTO `data` (`shorturl`, `url`, `clicks`, `ip`, `description`, `owner`, `timestamp`) 
VALUES ('mqfLH','http://www.yahoo.fr/',0,'127.0.0.1','This is a test.','god','1999-12-31 23:59:59');";
if($req = $bdd->exec($query) !== FALSE) {
?>
OK
<?php
} else {
?>
Problem!
<?php
}
?>


<br />
3/5 "data_deleted" 
<?php
$query = "DROP TABLE IF EXISTS `data_deleted`; 
CREATE TABLE `data_deleted` ( 
  `shorturl` varchar(8) NOT NULL DEFAULT '', 
  `url` varchar(255) NOT NULL DEFAULT '', 
  `clicks` int(99) NOT NULL DEFAULT '0', 
  `ip` varchar(64) NOT NULL DEFAULT '', 
  `description` varchar(255) NOT NULL DEFAULT '', 
  `owner` varchar(99) NOT NULL DEFAULT '', 
  `timestamp` datetime NOT NULL, 
  UNIQUE KEY `shorturl` (`shorturl`), 
  KEY `owner` (`owner`) 
) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
if($req = $bdd->exec($query) !== FALSE) {
?>
OK
<?php
} else {
?>
Problem!
<?php
}
?>


<br />
4/5 "stats" 
<?php
$query = "DROP TABLE IF EXISTS `stats`; 
CREATE TABLE `stats` ( 
  `shorturl` varchar(8) NOT NULL DEFAULT '', 
  `ip` varchar(64) NOT NULL DEFAULT '', 
  `useragent` varchar(99) NOT NULL DEFAULT '', 
  `referer` varchar(99) NOT NULL DEFAULT '', 
  `timestamp` datetime NOT NULL, 
  KEY `shorturl` (`shorturl`), 
  KEY `ip` (`ip`) 
) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
if($req = $bdd->exec($query) !== FALSE) {
?>
OK
<?php
} else {
?>
Problem!
<?php
}
?>


<br />
5/5 "stats_deleted" 
<?php
$query = "DROP TABLE IF EXISTS `stats_deleted`; 
CREATE TABLE `stats_deleted` (
  `shorturl` varchar(8) NOT NULL DEFAULT '', 
  `ip` varchar(64) NOT NULL DEFAULT '', 
  `useragent` varchar(99) NOT NULL DEFAULT '', 
  `referer` varchar(99) NOT NULL DEFAULT '', 
  `timestamp` datetime NOT NULL, 
  KEY `shorturl` (`shorturl`), 
  KEY `ip` (`ip`) 
) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
if($req = $bdd->exec($query) !== FALSE) {
?>
OK
<?php
} else {
?>
Problem!
<?php
}
?>




<br />
Create .htaccess file.
<?php

$htaccess = "############### Azizute start\n";
$htaccess .= "<IfModule mod_rewrite.c>\n";
$htaccess .= "	RewriteEngine On\n";
$htaccess .= "	RewriteBase /\n";
$htaccess .= "	RewriteCond %{REQUEST_FILENAME} !-f\n";
$htaccess .= "	RewriteCond %{REQUEST_FILENAME} !-d\n";
$htaccess .= "	RewriteRule ^.*$ /index.php [L]\n";
$htaccess .= "</IfModule>\n";
$htaccess .= "############### Azizute end\n";
if(!$buffer = fopen('.htaccess', "c")) {
?>
<br />
<b>/!\ Impossible to create .htaccess file.</b>
<?php
}

if(!file_put_contents('.htaccess', $htaccess)) {
?>
<br />
<b>/!\ Impossible to write in .htaccess file.</b>
<?php
}
?>
<br />
Install finished.
<br />
<br />
<br />
<br />
Go to <a href="/mqfLH" target="_blank">/mqfLH</a> for test.
<br />
<b>/!\ Delete this file (/installer.php). /!\</b>
