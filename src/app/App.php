<?php

namespace src\app;

use src\api\Request;

class App
{

    function __construct()
    {
        if (isset($_GET['api'])) {
            $api = new Request();
            echo $api->handle_request();
        } else {
            echo '<img src="assets/images/sample.jpg" with="100">';
        }
    }

}