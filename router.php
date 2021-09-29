<?php

$uri = explode("?", $_SERVER["REQUEST_URI"])[0];
if (in_array("QUERY_STRING", $_SERVER)) {
    $get_vars = explode("&", $_SERVER["QUERY_STRING"]);
    foreach ($get_vars as $get_var) {
        $get_var_pair = explode("=", $get_var);
        $_GET[$get_var_pair[0]] = ($get_var_pair[1] ?? "");
    }
}
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
