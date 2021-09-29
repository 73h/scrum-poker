<?php

use src\app\App;

file_put_contents('../uri.txt', $_SERVER["REQUEST_URI"]);
file_put_contents('../post.txt', json_encode($_POST));

require '../src/static/helpers.php';
require '../autoload.php';

new App();
