Azizut
======

Azizut is a private shortener designed to be as light and efficient as possible.



Respect of "Do Not Track".

QRCode support (add .qr).

Support cache (memcached) and async operations (/cron.php page each 5 minutes recommended).

Support Apache/Nginx

Authentification failure is logged for fail2ban support.


# Installation:

Configuration file in /config/ folder (do it first!).

htaccess and sql database is in root folder, for nginx, use ```location / { try_files $uri $uri/ /index.php; }``` directive.

For better performance, disable logging (eg. for apache, add "CustomLog /dev/null common" to vhost file)

If you want to create redirect domain, create (manually) an entry in the database (for the moment, the visits will be recorded).
```
INSERT INTO `data` (`shorturl`, `url`, `clicks`, `ip`, `description`, `owner`, `timestamp`) VALUES ('','http://www.zut.io/',0,'127.0.0.1','domain redirect','god','1999-12-31 23:59:59');
```

Azizut must be at the root of domain/ip.

Authorization access must be inserted manually (or with external API) in DB.

For async mode, use a cron (ex: each minutes) on /cron.php.


# Short API usage:

Dialog type is json (call & response),  php client class -> 
(https://git.alex.alouit.fr/cgi-bin/public/gitweb.cgi?p=azizut-client-class/.git;a=summary).

Response always contain Standard HTTP Status code (as statusCode field), data return always in data field.

All date/time must be/is as timestamp format.

Call always must contain auth (access field) for the moment, extras params must be in params field.

Exemple:

Call:

```
{
  "access": {
    "username": "JohnDoe",
    "password": "YWNlZGVjZDAwNjdiNjRkNjkyYWNkZTVmYjA4MGE3OTY"
  },
  "action": "insert",
  "params": {
    "url": "http://www.yahoo.co.uk/"
  }
}
```

Response:

```
{
  "data": {
    "link": "http://www.yahoo.co.uk/",
    "shorturl": "GQlIM",
    "description": "..."
  },
  "statusCode": 200
}
```

Call:

```
{
  "access": {
    "username": "JohnDoe",
    "password": "YWNlZGVjZDAwNjdiNjRkNjkyYWNkZTVmYjA4MGE3OTY"
  },
  "action": "get",
  "params": {
    "shorturl": "vEMQt"
  }
}
```

Response:

```
{
  "data": {
    "shorturl": "vEMQt",
    "link": "http://www.yahoo.co.uk/",
    "clicks": "0",
    "ip": "00.000.000.00",
    "description": "unknown",
    "owner": "JohnDoe",
    "timestamp": "2014-12-05 01:52:00"
  },
  "statusCode": 200
}
```


## - auth:

```
access: username password

(you can only deal with your own data).
```

## - create link

```
action: insert

params: url, [ secure ] ( bool )

(secure: shorten only if target link return a valid 200 status code (false by default))
```

## - update link

```
action: update

params: shorturl/url newShorturl/newUrl

(newShorturl could be empty string (like ""),  for new "random" shorturl).
```


## - delete link

```
action: delete

params: shorturl/url

(shorturl is faster than url).
```


## - get a link

```
action: get

params: shorturl/url [ stats ] ( bool )

(shorturl is faster than url).
```


## - get links

```
action: get

params: [ start ] ( int ), [ limit ] ( int )
```


Shorturl could be: "http://domain.tld/shorturl" or "domain.tld/shorturl" or "/shorturl" or "shorturl".
