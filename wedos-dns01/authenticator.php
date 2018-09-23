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

// Loop until updated DNS is online or for 15 minutes
$time = time();
while (true) {
	// Get DNS record
	$dns = dns_get_record('_acme-challenge' + CERTBOT_DOMAIN, DNS_TXT);
  
	if (sizeof($dns) > 0) {
		$ok = false;
		
		// Check if any DNS record is 
		foreach ($dns as $rec) {
			if ($rec['txt'] === CERTBOT_VALIDATION) {
				$ok = true;
				break;
			}		
		}
		
		if ($ok) break;
	}

	// Wait 10s before next request
	sleep(10);
		
	// Don't stay in the loop for longer than 15 minutes
	if ((time() - $time) >= (15 * 60)) break;
}

// Give some extra time to the DNS to make sure its updated everywhere
sleep(30);
