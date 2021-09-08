<?php

namespace src\api;

class Api extends Request
{

    public function initialize_routes(): void
    {
        $this->get('rooms/<room>/votes/<vote>', function ($room, $vote) {
            error_log(json_encode($room));
            error_log(json_encode($vote));
            $this->send_success(null);
        });
        $this->post('rooms', function ($data) {
            error_log(json_encode($data));
            $this->send_success(null);
        });
        $this->send_404();
    }

}