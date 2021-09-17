<?php

namespace src\api;

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

    private function generateRoomId()
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

    private function loadRoom()
    {
        if (!$this->roomExists()) {
            throw new NotFoundException('room does not exist');
        }
        $load = json_decode(file_get_contents($this->getRoomPath()), true);
        $this->room = new Room($load);
    }

    private function saveRoom()
    {
        file_put_contents($this->getRoomPath(), json_encode($this->room));
    }

    private function createRoom(string $owner, ?string $password = null)
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

    public function enterRoom(string $name)
    {
        $this->current_user_id = $this->room->getUserIdFromName($name);
        if (!$this->current_user_id) {
            if ($this->room->locked) {
                throw new ForbiddenException('room is locked, new users cannot enter');
            } else {
                $this->current_user_id = $this->room->addUser($name);
                $this->saveRoom();
            }
        }
    }

    public function getRoomResponse(): object
    {
        return (object)array(
            'room' => $this->room_id,
            'token' => $this->room->token . $this->getCurrentUser()->token,
            'users_id' => $this->current_user_id,
            'users' => $this->room->getUsers()
        );
    }

}