<?php

namespace src\api;

use Exception;
use src\api\exceptions\ForbiddenException;
use src\api\exceptions\NotFoundException;

class Api extends Request
{

    public function initialize_routes(): void
    {
        try {

            // create room
            $this->post('rooms', function ($data) {
                $poker = new Poker(owner: $data->user->name);
                if (property_exists($data, 'password')) $poker->setUserPassword($data->password);
                $this->sendCreated($poker->getRoomResponse());
            }, Structures::rooms());

            // enter room
            $this->post('rooms/<room>/users', function ($room_id, $data) {
                $poker = new Poker(room_id: $room_id);
                $password = property_exists($data, 'password') ? $data->password : null;
                $poker->enterRoom($data->name, $password);
                $this->sendCreated($poker->getRoomResponse());
            }, Structures::rooms_users());

            // get room
            $this->get('rooms/<room>', function ($room_id, $headers) {
                $poker = new Poker(room_id: $room_id);
                $poker->validateToken($headers->token);
                $this->sendSuccess($poker->getRoomResponse());
            }, ['token']);

        } catch (NotFoundException $e) {
            $this->sendNotFound(detail: $e->getMessage());
        } catch (ForbiddenException $e) {
            $this->sendForbidden(detail: $e->getMessage());
        } catch (Exception $e) {
            $this->sendInternalServerError(detail: $e->getMessage());
        }
        $this->sendNotFound();
    }

}