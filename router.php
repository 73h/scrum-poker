<?php

$uri = $_SERVER["REQUEST_URI"];

if (preg_match('/^\/?api\/.*$/', $uri)) {
    $_GET['api'] = preg_replace('/^\/?api\/(.*)$/', '$1', $uri);
    chdir('www/');
    require 'index.php';
} else if (preg_match('/^\/?[a-z\-]+\.html$/', $uri)) {
    $_GET['site'] = preg_replace('/^\/?([a-z\-]+)\.html$/', '$1', $uri);
    chdir('www/');
    require 'index.php';
} else {
    return false;
}
