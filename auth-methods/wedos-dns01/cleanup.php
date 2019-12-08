<?php
require_once __DIR__ . '/initialize.php';

$r = new \wedosDns01\dnsRowsListRequest();
$r->domain = REGISTRABLE_DOMAIN;
$list = wedosDns()->dnsRowsList($r);

foreach ($list as $dnsEntry) {
	if ($dnsEntry->name !== DNS_ENTRY_NAME)
		continue;
	if ($dnsEntry->rdata !== CERTBOT_VALIDATION)
		continue;
	$r = new \wedosDns01\dnsRowDeleteRequest();
	$r->domain = REGISTRABLE_DOMAIN;
	$r->row_id = $dnsEntry->ID;
	wedosDns()->dnsRowDelete($r);
}
