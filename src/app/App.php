<?php

namespace src\app;

use src\api\Api;

class App
{

    function __construct()
    {
        if (isset($_GET['api'])) {
            $api = new Api();
            $api->initialize_routes();
        } else {
            echo '<img src="assets/images/sample.jpg" with="100">';
        }
    }

}