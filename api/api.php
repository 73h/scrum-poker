<?php

$test_file = 'test.json';
if (!is_file($test_file)) {
    file_put_contents($test_file, '{"foo":"bar"}');
}

echo file_get_contents($test_file);
