<?php
require_once __DIR__ . '/initialize.php';

$r = new \wedosDns01\dnsRowAddRequest();
$r->domain = REGISTRABLE_DOMAIN;
$r->name = DNS_ENTRY_NAME;
$r->type = 'TXT';
$r->ttl = 600;
$r->rdata = CERTBOT_VALIDATION;
$done = wedosDns()->dnsRowAdd($r);

if ($done !== true) {
	throw new Exception('Failed to create a DNS entry, Wedos WAPI error: ' . $done);
}

$r = new \wedosDns01\dnsDomainCommitRequest();
$r->name = REGISTRABLE_DOMAIN;
wedosDns()->dnsDomainCommit($r);

// Loop until updated DNS is online or for 20 minutes
$waitForPropagation = function () {
	$startTime = time();
	while (true) {
		$records = dns_get_record('_acme-challenge.' . CERTBOT_DOMAIN, DNS_TXT);

		if (count($records)) {
			foreach ($records as $rec) {
				if (isset($rec['txt']) && $rec['txt'] === CERTBOT_VALIDATION) {
					return true;
				}
			}
		}

		if ((time() - $startTime) >= (20 * 60))
			return false;

		sleep(30);
	}
	return false;
};
$waitForPropagation();

// Give some extra time to the DNS to make sure its updated everywhere
sleep(2.5 * 60);
