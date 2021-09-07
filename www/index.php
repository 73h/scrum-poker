<?php
error_reporting(E_ALL);
ini_set("log_errors", 1);
ini_set("error_log", "../../logs/error.log");
session_start();
require '../autoload.php';
new \src\app\App();
