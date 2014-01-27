rest-exercise
=============

A simple REST exercise in PHP using the Slim framework.

All routes are available under `web/index.php`.

E.g. to GET all users:  
`http://<my-site>/web/index.php/api/users`

## Installation ##
To use, make sure you copy `default.settings.php` to `settings.php` and configure the settings accordingly.

For ease-of-use you may wish to setup a virtual host that points to `/web`.

## Authentication ##

To authenticate, you may either send your user credentials via HTTP Basic with each request or use the `api/auth` route.

To use `api/auth`, send a GET request with your user details via HTTP Basic. It will then respond with a `X-Session-Id` header.

You can then use this header and send it with all proceeding requests to the API. Session timeout can be configured in `settings.php`.
