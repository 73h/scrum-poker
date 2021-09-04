<?php

$uri = $_SERVER["REQUEST_URI"];

if (preg_match('/^\/?api\/.*$/', $uri)) {
    $_GET['api'] = preg_replace('/^\/?api\/(.*)$/', '$1', $uri);
    include 'index.php';
} else {
    return false;
}
