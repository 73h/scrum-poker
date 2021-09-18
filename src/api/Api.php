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
            }, Structures::rooms_users());

            // enter room
            $this->post('rooms/<room>/users', function ($room_id, $data) {
                $poker = new Poker(room_id: $room_id);
                $user_password = property_exists($data->user, 'password') ? $data->user->password : null;
                $room_password = property_exists($data, 'room') ?
                    (property_exists($data->room, 'password') ? $data->room->password : null) : null;
                $poker->enterRoom($data->user->name, $user_password, $room_password);
                $this->sendCreated($poker->getRoomResponse());
            }, Structures::rooms_users());

            // start vote
            $this->post('rooms/<room>/votes', function ($room_id, $data, $headers) {
                $poker = new Poker(room_id: $room_id);
                $poker->validateToken($headers->token);
                $poker->startVote();
                $this->sendSuccess($poker->getRoomResponse());
            }, Structures::empty(), ['token']);

            // vote
            $this->post('rooms/<room>/votes/<vote>', function ($room_id, $vote_id, $data, $headers) {
                $poker = new Poker(room_id: $room_id);
                $poker->validateToken($headers->token);
                $poker->vote($vote_id, $data->card);
                $this->sendOk('voted');
            }, Structures::vote(), ['token']);

            // reveal vote
            $this->patch('rooms/<room>/votes/<vote>', function ($room_id, $vote_id, $data, $headers) {
                $poker = new Poker(room_id: $room_id);
                $poker->validateToken($headers->token);
                $message = [];
                if ($data->task == 'reveal') {
                    $poker->revealVote($vote_id);
                    $message[] = 'vote revealed';
                }
                $this->sendOk(implode(', ', $message));
            }, Structures::vote_patch(), ['token']);

            // patch room
            $this->patch('rooms/<room>', function ($room_id, $data, $headers) {
                if ($data !== null) {
                    $poker = new Poker(room_id: $room_id);
                    $poker->validateToken($headers->token);
                    $message = [];
                    if (property_exists($data, 'password')) {
                        $poker->setRoomPassword($data->password);
                        $message[] = 'room password changed';
                    }
                    $this->sendOk(implode(', ', $message));
                } else {
                    $this->sendBadRequest(detail: 'payload is empty');
                }
            }, Structures::empty(), ['token']);

            // get room
            $this->get('rooms/<room>', function ($room_id, $headers) {
                $poker = new Poker(room_id: $room_id);
                $poker->validateToken($headers->token);
                $this->sendSuccess($poker->getRoomResponse());
            }, ['token']);

            // get card sets
            $this->get('cards', function () {
                $this->sendSuccess(Cards::allCards());
            });

            // get card set
            $this->get('cards/<cardset>', function ($cardset) {
                if (property_exists(Cards::allCards(), $cardset)) {
                    $this->sendSuccess(Cards::allCards()->$cardset);
                } else {
                    $this->sendNotFound(detail: 'cardset not found');
                }
            });

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