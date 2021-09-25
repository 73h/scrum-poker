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
} else if (preg_match('/[a-z0-9]{6}$/', $uri)) {
    $_GET['session'] = preg_replace('/.*([a-z0-9]{6})$/', '$1', $uri);
    $_GET['site'] = 'index';
    chdir('www/');
    require 'index.php';
} else {
    return false;
}
