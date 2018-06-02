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
