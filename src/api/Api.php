<?php

namespace src\api;

use Exception;
use src\api\exceptions\NotFoundException;

class Api extends Request
{

    private ?string $token = null;

    public function __construct()
    {
        parent::__construct();
        $this->token = $this->getHeader('token');
    }

    private function tokenExists()
    {
        if ($this->token == null) {
            $this->sendBadRequest(detail: 'token header is missing');
        }
    }

    private function validateToken(Poker $poker)
    {
        $this->tokenExists();
        $token_set = str_split($this->token, strlen($this->token) / 2);
        $room_token = $token_set[0];
        $user_token = $token_set[1];
        if (!$poker->validateRoomToken($room_token) || !$poker->validateUserToken($user_token)) {
            $this->sendForbidden(detail: 'token is invalid');
        }
    }

    public function initialize_routes(): void
    {
        try {

            // create room
            $this->post('rooms', ['name'], function ($data) {
                $poker = new Poker(owner: $data->name);
                $this->sendCreated($poker->getRoomResponse());
            });

            // enter room
            $this->post('rooms/<room>/users', ['name'], function ($room_id, $data) {
                $poker = new Poker(room_id: $room_id);
                $poker->enterRoom($data->name);
                $this->sendCreated($poker->getRoomResponse());
            });

            // get room
            $this->get('rooms/<room>', function ($room_id) {
                $this->tokenExists();
                $poker = new Poker(room_id: $room_id);
                $this->validateToken($poker);
                $this->sendSuccess($poker->getRoomResponse());
            });

        } catch (NotFoundException $e) {
            $this->sendNotFound(detail: $e->getMessage());
        } catch (Exception $e) {
            $this->sendInternalServerError(detail: $e->getMessage());
        }
        $this->sendNotFound();
    }

}