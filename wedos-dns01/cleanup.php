<?php
require_once __DIR__ . '/initialize.php';

$r = new \wedosDns01\dnsRowsListRequest();
$r->domain = $secondLevelDomain;
$list = wedosDns()->dnsRowsList($r);

foreach ($list as $dnsEntry) {
	if ($dnsEntry->name !== $dnsName)
		continue;
	if ($dnsEntry->rdata !== CERTBOT_VALIDATION)
		continue;
	$r = new \wedosDns01\dnsRowDeleteRequest();
	$r->domain = CERTBOT_DOMAIN;
	$r->row_id = $dnsEntry->ID;
	wedosDns()->dnsRowDelete($r);
}
