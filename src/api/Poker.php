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
        $this->room_id = '';
        $characters = 'abcdefhkmnorstuvwxyz123456789';
        $max = strlen($characters);
        for ($i = 0; $i < self::ROOM_ID_LENGTH; $i++) {
            $number = random_int(0, $max - 1);
            $character = $characters[$number];
            $this->room_id .= $character;
        }
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
        $this->room->owner = $owner;
        $this->room->users[] = $owner;
        $this->generateRoomId();
        $this->saveRoom();
    }


    public function getRoomResponse()
    {
        return (object)array(
            'room' => $this->room_id,
            'owner' => $this->room->owner,
            'users' => $this->room->users
        );
    }

}