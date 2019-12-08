<?php

require_once __DIR__ . '/WedosWapi.php';
require_once __DIR__ . '/WedosDns.php';
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/config.php';

function wedosDns() {
	static $instance;
	if (!$instance)
		$instance = new \wedosDns01\WedosDns(WEDOS_USER, WEDOS_PASS);
	return $instance;
}

$cacheDir = realpath(__DIR__ . '/../../cache');
if (!file_exists($cacheDir) || !is_writable($cacheDir)) {
    throw new Exception('Cache dir doesn\'t exist or is not writable.');
}
if (!extension_loaded('intl')) {
    throw new Exception('PHP is missing intl extension.');
}
$manager = new \Pdp\Manager(new \Pdp\Cache($cacheDir), new \Pdp\CurlHttpClient(), 86400);
$rules = $manager->getRules();
$domain = $rules->resolve('_acme-challenge.' . CERTBOT_DOMAIN);

define('REGISTRABLE_DOMAIN', $domain->getRegistrableDomain());
define('DNS_ENTRY_NAME', $domain->getSubDomain());
