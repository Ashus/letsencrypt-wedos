<?php
require_once __DIR__ . '/initialize.php';

$r = new \wedosDns01\dnsRowAddRequest();
$r->domain = CERTBOT_DOMAIN;
$r->name = '_acme-challenge';
$r->type = 'TXT';
$r->ttl = 600;
$r->rdata = CERTBOT_VALIDATION;
$done = wedosDns()->dnsRowAdd($r);

if ($done !== true) {
	throw new Exception('Failed to create a DNS entry, Wedos WAPI error: ' . $done);
}

$r = new \wedosDns01\dnsDomainCommitRequest();
$r->name = CERTBOT_DOMAIN;
wedosDns()->dnsDomainCommit($r);

sleep(500);
