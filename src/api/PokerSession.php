<?php

namespace src\api;

use Exception;

class PokerSession
{

    public ?string $owner = null;
    public object $users;
    public string $token;
    public int $created;
    public bool $locked = false;
    public ?string $password = null;
    public string $card_set = 'default';
    public object $votes;

    /**
     * @throws Exception
     */
    public function __construct(?array $load = null)
    {
        $this->created = time();
        $this->users = (object)array();
        $this->token = getRandomString(16);
        $this->votes = (object)array();
        if ($load !== null) {
            $this->load($this, $load);
        }
    }

    private function load(mixed &$obj, array $load): void
    {
        foreach ($load as $key => $value) {
            if (is_array($value)) {
                if (is_object($obj)) {
                    if (!property_exists($obj, $key)) {
                        $obj->{$key} = (object)array();
                    }
                    $this->load($obj->{$key}, $value);
                } elseif (is_array($obj)) {
                    array_push($obj, (object)array());
                    $this->load($obj[$key], $value);
                }
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
                'token' => getRandomString(16),
                'password' => null
            );
            if ($this->owner === null) $this->owner = $user_id;
        }
        return $user_id;
    }

    public function getUserNames(): object
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

    public function getCurrentVoteKey(): int
    {
        return count(get_object_vars($this->votes));
    }

    public function getCurrentVote(): ?object
    {
        if ($this->getCurrentVoteKey() == 0) return null;
        else return $this->votes->{$this->getCurrentVoteKey()};
    }

    public function addVote(): void
    {
        $last_vote = $this->getCurrentVote();
        if ($last_vote === null || $last_vote->revealed !== null) {
            $votes = (object)array();
            $this->votes->{strval(count(get_object_vars($this->votes)) + 1)} = (object)array(
                'started' => time(),
                'votes' => $votes,
                'revealed' => null,
                'card_set' => $this->card_set
            );
        }
    }

}
