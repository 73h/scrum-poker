<?php

namespace src\api;

use Exception;
use src\api\exceptions\NotFoundException;

class Poker
{

    const ROOM_PATH = '../rooms/';
    const ROOM_ID_LENGTH = 6;
    private Room $room;
    private string $room_id;
    private int $existing_room_id_counter = 0;
    private string $user_id;

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

    private function getRoomPath()
    {
        return self::ROOM_PATH . $this->room_id . '.json';
    }

    private function roomExists()
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

    private function createRoom(string $owner)
    {
        $this->room = new Room();
        $this->user_id = $this->room->addUser($owner);
        $this->generateRoomId();
        $this->saveRoom();
    }

    public function validateUserToken(string $token): bool
    {
        foreach (get_object_vars($this->room->users) as $key => $user) {
            if ($user->token == $token) {
                $this->user_id = $key;
                return true;
            }
        }
        return false;
    }

    public function validateRoomToken(string $token): bool
    {
        return $this->room->token === $token;
    }

    public function enterRoom(string $name)
    {
        if ($this->room->password !== null) {
            // ToDo: Exception Raum gesperrt
        } else {
            $this->user_id = $this->room->addUser($name);
            $this->saveRoom();
        }
    }

    public function getRoomResponse(): object
    {
        return (object)array(
            'room' => $this->room_id,
            'token' => $this->room->token . $this->room->users->{$this->user_id}->token,
            'users_id' => $this->user_id,
            'users' => $this->room->getUsers()
        );
    }

}