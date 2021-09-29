<?php

use src\app\App;

file_put_contents('../x.txt', $_SERVER["REQUEST_URI"]);

require '../src/static/helpers.php';
require '../autoload.php';

new App();
