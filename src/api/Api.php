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

            // start session
            $this->post('sessions', function ($data) {
                $poker = new Poker(owner: $data->user->name, card_set: $data->session->card_set);
                if (property_exists($data->user, 'password')) {
                    $poker->setUserPassword($data->user->password);
                }
                if (property_exists($data->session, 'password')) {
                    $poker->setSessionPassword($data->session->password);
                }
                $this->sendCreated($poker->getSessionResponse());
            }, Structures::sessions());

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
            }, Structures::empty(), (object)['token' => (object)['required' => true]]);

            // vote
            $this->post('sessions/<session>/votes/<vote>', function ($session_id, $vote_id, $data, $headers) {
                $poker = new Poker(session_id: $session_id);
                $poker->validateToken($headers->token);
                $poker->vote($vote_id, $data->card);
                $this->sendOk('voted');
            }, Structures::vote(), (object)['token' => (object)['required' => true]]);

            // uncover vote
            $this->put('sessions/<session>/votes/<vote>', function ($session_id, $vote_id, $data, $headers) {
                $poker = new Poker(session_id: $session_id);
                $poker->validateToken($headers->token);
                $message = [];
                if ($data->task == 'uncover') {
                    $poker->uncoverVoting($vote_id);
                    $message[] = 'voting uncovered';
                }
                $this->sendOk(implode(', ', $message));
            }, Structures::vote_put(), (object)['token' => (object)['required' => true]]);

            // put session
            $this->put('sessions/<session>', function ($session_id, $data, $headers) {
                if ($data !== null) {
                    $poker = new Poker(session_id: $session_id);
                    $poker->validateToken($headers->token);
                    $message = [];
                    if (property_exists($data, 'password')) {
                        $poker->setSessionPassword($data->password);
                        $message[] = 'session password changed';
                    } elseif (property_exists($data, 'new_owner_user_id')) {
                        $poker->changeOwner($data->new_owner_user_id);
                        $message[] = 'session owner changed';
                    }
                    $this->sendOk(implode(', ', $message));
                } else {
                    $this->sendBadRequest(detail: 'payload is empty');
                }
            }, Structures::empty(), (object)['token' => (object)['required' => true]]);

            // get session
            $this->get('sessions/<session>', function ($session_id, $headers) {
                $poker = new Poker(session_id: $session_id);
                if ($headers->token !== null) {
                    $poker->validateToken($headers->token);
                    $this->sendSuccess($poker->getSessionResponse());
                } else {
                    $this->sendSuccess($poker->getBasicSessionResponse());
                }
            }, (object)['token' => (object)['required' => false]]);

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

            // start session from slack webhook
            $this->post('slack', function () {
                $slack = new Slack();
                if (isset($_POST["token"])) {
                    if ($slack->validateToken($_POST["token"])) {
                        if (isset($_POST["text"])) {
                            $cardset = ($_POST["text"] == "" ? "default" : $_POST["text"]);
                            if (property_exists(Cards::allCards(), $cardset)) {
                                $poker = new Poker(card_set: $cardset);
                                $this->sendSuccess($slack->getSlackResponse($poker));
                            } else {
                                $this->sendSuccess($slack->getSlackCardSetErrorResponse());
                            }
                        }
                        $this->sendSuccess('');
                    }
                }
                $this->sendSuccess($slack->getSlackErrorResponse());
            }, Structures::empty());

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