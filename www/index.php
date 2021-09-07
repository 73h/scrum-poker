<?php

error_reporting(E_ALL);
ini_set('ignore_repeated_errors', TRUE);
ini_set('display_errors', FALSE);
ini_set('log_errors', TRUE);
ini_set('error_log', '../../../../logs/error.log');

use src\app\App;

session_start();
require '../autoload.php';
new App();
