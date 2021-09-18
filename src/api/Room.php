<?php

namespace src\api;

use Exception;

class Room
{

    public ?string $owner = null;
    public object $users;
    public string $token;
    public int $created;
    public bool $locked = false;
    public ?string $password = null;
    public string $card_set = 'default';

    /**
     * @throws Exception
     */
    public function __construct(?array $load = null)
    {
        $this->created = time();
        $this->users = (object)array();
        $this->token = getRandomString(TOKEN_LENGTH);
        if ($load !== null) {
            $this->load($this, $load);
        }
    }

    private function load(object $obj, array $load)
    {
        foreach ($load as $key => $value) {
            if (is_array($value)) {
                if (!property_exists($obj, $key)) {
                    $obj->{$key} = (object)array();
                }
                $this->load($obj->{$key}, $value);
            } else {
                $obj->{$key} = $value;
            }
        }
    }

    /**
     * @throws Exception
     */
    public function addUser(string $name): string
    {
        $users = array();
        foreach (get_object_vars($this->users) as $key => $user) {
            $users[$key] = $user->name;
        }
        $user_id = array_search($name, $users);
        if (!$user_id) {
            $user_id = strval(count(get_object_vars($this->users)) + 1);
            $this->users->{$user_id} = (object)array(
                'name' => $name,
                'token' => getRandomString(TOKEN_LENGTH),
                'password' => null
            );
            if ($this->owner === null) $this->owner = $user_id;
        }
        return $user_id;
    }

    public function getUsers(): object
    {
        $users = (object)array();
        foreach (get_object_vars($this->users) as $key => $user) {
            $users->{$key} = (object)array('name' => $user->name);
        }
        return $users;
    }

    public function getUserIdFromName(string $name): ?string
    {
        $users = array();
        foreach (get_object_vars($this->users) as $key => $user) {
            $users[$key] = $user->name;
        }
        return array_search($name, $users);
    }

}
