<?php

namespace src\app;

use src\api\Api;
use src\views\Index;
use src\views\Manual;

class App
{

    function __construct()
    {
        if (isset($_GET['api'])) {
            $api = new Api();
            $api->initialize_routes();
        } else {
            $site = $_GET['site'] ?? 'index';
            switch ($site) {
                case 'index':
                    new Index();
                    break;
                case 'manual':
                    new Manual();
                    break;
                default:
                    http_response_code(404);
            }
        }
    }

}