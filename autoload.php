<?php

spl_autoload_register(function ($class_name) {
    error_log($class_name . '.php');
    error_log(__DIR__);
    include $class_name . '.php';
});