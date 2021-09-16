<?php

namespace src\api;

use Exception;
use src\api\exceptions\NotFoundException;

class Api extends Request
{

    public function initialize_routes(): void
    {
        try {

            // get room
            $this->get('rooms/<room>', function ($room_id) {
                $poker = new Poker(room_id: $room_id);
                $this->sendSuccess($poker->getRoomResponse());
            });

            // create room
            $this->post('rooms', ['name'], function ($data) {
                $poker = new Poker(owner: $data->name);
                $this->sendCreated($poker->getRoomResponse());
            });

        } catch (NotFoundException $e) {
            $this->sendNotFound(detail: $e->getMessage());
        } catch (Exception $e) {
            $this->sendInternalServerError(detail: $e->getMessage());
        }
        $this->sendNotFound();
    }

}