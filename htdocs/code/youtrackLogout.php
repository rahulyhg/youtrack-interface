<?php
/**
 * clear login cookie.
 */
require_once __DIR__ . '/../../vendor/autoload.php';
use Youtrackinterfacer\youtrackLogout;

$youtrackLogout = new youtrackLogout;
$youtrackLogout->execute();