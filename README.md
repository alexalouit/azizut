Azizut
======

Azizut is a private shortener designed to be as light and efficient as possible



Respect of "Do Not Track"

QRCode support (add .qr)

Authentification failure is logged for fail2ban support



# For the moment:

- no gui

- no cache (memcached & apc planned)

- username and password must be set manually in db (strongly recommended to not store it "in clear", base64 encode with salt minimum..)

- no cron (when cache is functional, insertion of visitor logs (stats) must be delayed and processed by packet.)

- must be at the root of the web server

- support only apache

- robots.txt not support

- stats is not working

- unified class guest/api (need to separate them for fastest process


# Installation:

Configuration file in /config/ folder (do it first!)

Automatic: go to ./installer.php page

Manual: see files in manual_installation folder




# Short API usage:

(php client class coming soon), dialog type is json (call & response), 

response always contain Standard HTTP Status code (as statusCode field), data return always in data field.

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
    "url": "http://www.yahoo.co.uk/",
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
    "url": "http://www.yahoo.co.uk/",
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

access: username password

(you can only deal with your own data)


## - create link

action: insert

params: url


## - update link

action: update

params: shorturl/url newShorturl/newUrl

(newShorturl could be empty string (like ""),  for new "random" shorturl)



## - delete link

action: delete

params: shorturl/url

(shorturl is faster than url)



## - get a link

action: get

params: shorturl/url [stats](bool)

(shorturl is faster than url)


## - get links

action: get

params: [start](int), [limit](int)


All shorturl could be: "http://domain.tld/shorturl" or "domain.tld/shorturl" or "/shorturl" or "shorturl"  *only shorturl work for the moment*