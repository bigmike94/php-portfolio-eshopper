<?php

//front controller
//General settings
ini_set('display_error', 1);
error_reporting(E_ALL);

date_default_timezone_set("Asia/Tbilisi");

define('ROOT', dirname(__FILE__));
define('LANGS', array("ge", "en"));//allowed languages
define("DEFAULT_LANG", "ge");
require_once(ROOT.'/components/Autoload.php');
$router = new Router();
$router->run();
?>