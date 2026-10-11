<?php
$vendor_path = getenv('VENDOR_RELATIVE_PATH') ?: '/vendor';
$autoload = $vendor_path . '/autoload.php';
if (is_file($autoload)) {
  require_once $autoload;
} else {
  error_log("Error:
    autoload.php not found at {$autoload}");
}

/*if (session_status() === PHP_SESSION_NONE) {
  \utils\configureCookies();
  session_start();
}//*/
