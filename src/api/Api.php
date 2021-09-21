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

            // create session
            $this->post('sessions', function ($data) {
                $poker = new Poker(owner: $data->user->name);
                if (property_exists($data, 'password')) $poker->setUserPassword($data->password);
                $this->sendCreated($poker->getSessionResponse());
            }, Structures::sessions_users());

            // enter session
            $this->post('sessions/<session>/users', function ($session_id, $data) {
                $poker = new Poker(session_id: $session_id);
                $user_password = property_exists($data->user, 'password') ? $data->user->password : null;
                $session_password = property_exists($data, 'session') ?
                    (property_exists($data->session, 'password') ? $data->session->password : null) : null;
                $poker->enterSession($data->user->name, $user_password, $session_password);
                $this->sendCreated($poker->getSessionResponse());
            }, Structures::sessions_users());

            // start vote
            $this->post('sessions/<session>/votes', function ($session_id, $data, $headers) {
                $poker = new Poker(session_id: $session_id);
                $poker->validateToken($headers->token);
                $poker->startVote();
                $this->sendSuccess($poker->getSessionResponse());
            }, Structures::empty(), ['token']);

            // vote
            $this->post('sessions/<session>/votes/<vote>', function ($session_id, $vote_id, $data, $headers) {
                $poker = new Poker(session_id: $session_id);
                $poker->validateToken($headers->token);
                $poker->vote($vote_id, $data->card);
                $this->sendOk('voted');
            }, Structures::vote(), ['token']);

            // reveal vote
            $this->put('sessions/<session>/votes/<vote>', function ($session_id, $vote_id, $data, $headers) {
                $poker = new Poker(session_id: $session_id);
                $poker->validateToken($headers->token);
                $message = [];
                if ($data->task == 'reveal') {
                    $poker->revealVote($vote_id);
                    $message[] = 'vote revealed';
                }
                $this->sendOk(implode(', ', $message));
            }, Structures::vote_put(), ['token']);

            // put session
            $this->put('sessions/<session>', function ($session_id, $data, $headers) {
                if ($data !== null) {
                    $poker = new Poker(session_id: $session_id);
                    $poker->validateToken($headers->token);
                    $message = [];
                    if (property_exists($data, 'password')) {
                        $poker->setSessionPassword($data->password);
                        $message[] = 'session password changed';
                    }
                    $this->sendOk(implode(', ', $message));
                } else {
                    $this->sendBadRequest(detail: 'payload is empty');
                }
            }, Structures::empty(), ['token']);

            // get session
            $this->get('sessions/<session>', function ($session_id, $headers) {
                $poker = new Poker(session_id: $session_id);
                $poker->validateToken($headers->token);
                $this->sendSuccess($poker->getSessionResponse());
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