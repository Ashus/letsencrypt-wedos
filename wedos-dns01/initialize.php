<?php

require_once __DIR__ . '/WedosWapi.php';
require_once __DIR__ . '/WedosDns.php';
require_once __DIR__ . '/config.php';

function wedosDns() {
	static $instance;
	if (!$instance)
		$instance = new \wedosDns01\WedosDns(WEDOS_USER, WEDOS_PASS);
	return $instance;
}

$domainParts = explode('.', CERTBOT_DOMAIN);
$dnsName = '_acme-challenge';
$secondLevelDomain = implode('.', (array_slice($domainParts, -2, 2)));

if (count($domainParts) > 2) {
	array_splice($domainParts, -2);
	$dnsName .= '.' . implode('.', $domainParts);
}
