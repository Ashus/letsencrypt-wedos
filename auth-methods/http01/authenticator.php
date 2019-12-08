<?php
require_once __DIR__ . '/initialize.php';

$dir = certbot_file_dir();
if (!file_exists($dir)) {
	@mkdir($dir, 0777, true);
}

$filepath = certbot_file_path();
file_put_contents($filepath, CERTBOT_VALIDATION);
if (function_exists('chmod'))
	@chmod($filepath, 0444);
