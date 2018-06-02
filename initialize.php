<?php

define('CERTBOT_VALIDATION', !empty($_SERVER['CERTBOT_VALIDATION']) ? (string)$_SERVER['CERTBOT_VALIDATION'] : null);
define('CERTBOT_DOMAIN', !empty($_SERVER['CERTBOT_DOMAIN']) ? (string)$_SERVER['CERTBOT_DOMAIN'] : null);
define('CERTBOT_TOKEN', !empty($_SERVER['CERTBOT_TOKEN']) ? (string)$_SERVER['CERTBOT_TOKEN'] : null);

if (CERTBOT_TOKEN) {
	$method = 'http01';
} else {
	$method = 'wedos-dns01';
}
define('AUTH_METHOD', $method);
