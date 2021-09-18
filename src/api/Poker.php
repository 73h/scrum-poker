<?php

namespace src\api;

use DateTime;
use Exception;
use src\api\exceptions\ForbiddenException;
use src\api\exceptions\NotFoundException;

class Poker
{

    const ROOM_PATH = '../rooms/';
    const ROOM_ID_LENGTH = 6;
    private Room $room;
    private string $room_id;
    private int $existing_room_id_counter = 0;
    private string $current_user_id;

    /**
     * @throws NotFoundException
     * @throws Exception
     */
    function __construct(?string $room_id = null, ?string $owner = null)
    {
        if (!is_dir(self::ROOM_PATH)) {
            mkdir(self::ROOM_PATH);
        }
        if ($room_id === null) {
            $this->createRoom($owner);
        } else {
            $this->room_id = $room_id;
            $this->loadRoom();
        }
    }

    private function getRoomPath(): string
    {
        return self::ROOM_PATH . $this->room_id . '.json';
    }

    private function roomExists(): bool
    {
        $file_path = $this->getRoomPath();
        $room_exists = is_file($file_path);
        clearstatcache(true, $file_path);
        return $room_exists;
    }

    /**
     * @throws Exception
     */
    private function generateRoomId(): void
    {
        $this->room_id = getRandomString(self::ROOM_ID_LENGTH);
        $max = strlen(RANDOM_CHARACTERS);
        if ($this->roomExists()) {
            if ($this->existing_room_id_counter > pow($max, self::ROOM_ID_LENGTH)) {
                throw new Exception('no more rooms can be created');
            }
            $this->existing_room_id_counter++;
            $this->generateRoomId();
        }
    }

    /**
     * @throws NotFoundException
     * @throws Exception
     */
    private function loadRoom(): void
    {
        if (!$this->roomExists()) {
            throw new NotFoundException('room does not exist');
        }
        $load = json_decode(file_get_contents($this->getRoomPath()), true);
        $this->room = new Room($load);
    }

    private function saveRoom(): void
    {
        //file_put_contents($this->getRoomPath(), json_encode($this->room, JSON_PRETTY_PRINT));
        file_put_contents($this->getRoomPath(), json_encode($this->room));
    }

    /**
     * @throws Exception
     */
    private function createRoom(string $owner, ?string $password = null): void
    {
        $this->room = new Room();
        $this->current_user_id = $this->room->addUser($owner);
        if ($password !== null) $this->getCurrentUser()->password = $password;
        $this->generateRoomId();
        $this->saveRoom();
    }

    private function getCurrentUser(): object
    {
        return $this->room->users->{$this->current_user_id};
    }

    public function validateUserToken(string $token): bool
    {
        foreach (get_object_vars($this->room->users) as $key => $user) {
            if ($user->token == $token) {
                $this->current_user_id = $key;
                return true;
            }
        }
        return false;
    }

    public function validateRoomToken(string $token): bool
    {
        return $this->room->token === $token;
    }

    public function setUserPassword(string $password)
    {
        $this->getCurrentUser()->password = password_hash($password, PASSWORD_DEFAULT);
        $this->saveRoom();
    }

    /**
     * @throws ForbiddenException
     */
    public function userIsOwner(): bool
    {
        if ($this->current_user_id !== $this->room->owner) {
            throw new ForbiddenException('you are not the owner');
        }
        return true;
    }

    /**
     * @throws ForbiddenException
     */
    public function setRoomPassword(string $password): void
    {
        if ($this->userIsOwner()) {
            $this->room->password = password_hash($password, PASSWORD_DEFAULT);
            $this->saveRoom();
        }
    }

    /**
     * @throws ForbiddenException
     * @throws Exception
     */
    public function enterRoom(string $name, ?string $user_password = null, ?string $room_password = null): void
    {
        $this->current_user_id = $this->room->getUserIdFromName($name);
        if (!$this->current_user_id) {
            if ($this->room->locked) {
                throw new ForbiddenException('room is locked, new users cannot enter');
            } else {
                $this->current_user_id = $this->room->addUser($name);
                if ($user_password !== null) $this->setUserPassword($user_password);
                $this->saveRoom();
            }
        } else {
            if ($this->getCurrentUser()->password !== null &&
                !password_verify($user_password, $this->getCurrentUser()->password)) {
                throw new ForbiddenException('incorect user password');
            }
            if ($this->room->password !== null &&
                !password_verify($room_password, $this->room->password)) {
                throw new ForbiddenException('incorect room password');
            }
        }
    }

    /**
     * @throws ForbiddenException
     */
    public function validateToken(string $token): void
    {
        $token_set = str_split($token, strlen($token) / 2);
        $room_token = $token_set[0];
        $user_token = $token_set[1];
        if (!$this->validateRoomToken($room_token) || !$this->validateUserToken($user_token)) {
            throw new ForbiddenException('token is invalid');
        }
    }

    /**
     * @throws ForbiddenException
     */
    public function startVote()
    {
        if ($this->userIsOwner()) {
            $this->room->addVote();
            $this->saveRoom();
        }
    }

    /**
     * @throws ForbiddenException
     * @throws NotFoundException
     */
    public function vote(string $vote_id, string $card_id): void
    {
        if (!property_exists($this->room->votes, $vote_id)) {
            throw new NotFoundException('vote not found');
        }
        $vote = $this->room->votes->{$vote_id};
        if ($vote->revealed !== null) {
            throw new ForbiddenException('vote is already closed');
        }
        $card_set = Cards::allCards()->{$vote->card_set};
        if (!property_exists($card_set, $card_id)) {
            throw new NotFoundException('card not found');
        }
        $vote->votes->{$this->current_user_id} = (object)array(
            'card' => $card_id,
            'voted' => time()
        );
        $this->saveRoom();
    }

    /**
     * @throws ForbiddenException
     * @throws NotFoundException
     */
    public function revealVote(string $vote_id): void
    {
        if ($this->userIsOwner()) {
            if (!property_exists($this->room->votes, $vote_id)) {
                throw new NotFoundException('vote not found');
            }
            $vote = $this->room->votes->{$vote_id};
            if ($vote->revealed !== null) {
                throw new ForbiddenException('vote is already closed');
            }
            $vote->revealed = time();
            $this->saveRoom();
        }
    }

    /**
     * @throws Exception
     */
    public function getRoomResponse(): object
    {
        $current_vote = $this->room->getCurrentVote();
        $current_vote_response = null;
        if ($current_vote !== null) {
            $user_voted = array_map(function ($key) {
                return strval($key);
            }, array_keys(get_object_vars($current_vote->votes)));
            $votes = null;
            if ($current_vote->revealed !== null) {
                $votes = (object)array();
                foreach ($current_vote->votes as $user_id => $vote) {
                    $voted = (new DateTime)->setTimestamp($vote->voted)->format(DATE_ATOM);
                    $votes->{$user_id} = (object)array(
                        'card' => $vote->card,
                        'voted' => $voted
                    );
                }
            }
            // $started = (new DateTime('now', new DateTimeZone('Europe/Berlin')))->setTimestamp($current_vote->started);
            $started = (new DateTime)->setTimestamp($current_vote->started)->format(DATE_ATOM);
            $revealed = null;
            if (($current_vote->revealed !== null)) {
                $revealed = (new DateTime)->setTimestamp($current_vote->revealed)->format(DATE_ATOM);
            }
            $current_vote_response = (object)array(
                'key' => $this->room->getCurrentVoteKey(),
                'revealed' => $revealed,
                'started' => $started,
                'votes' => $votes,
                'user_voted' => $user_voted
            );
        }
        return (object)array(
            'room' => $this->room_id,
            'token' => $this->room->token . $this->getCurrentUser()->token,
            'users_id' => $this->current_user_id,
            'users' => $this->room->getUserNames(),
            'owner' => $this->room->owner,
            'card_set' => $this->room->card_set,
            'current_vote' => $current_vote_response
        );
    }

}
