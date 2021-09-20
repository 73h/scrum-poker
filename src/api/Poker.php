<?php

namespace src\api;

use DateTime;
use Exception;
use src\api\exceptions\ForbiddenException;
use src\api\exceptions\NotFoundException;

class Poker
{

    const SESSION_PATH = '../sessions/';
    const SESSION_ID_LENGTH = 6;
    private PokerSession $session;
    private string $session_id;
    private int $existing_session_id_counter = 0;
    private string $current_user_id;

    /**
     * @throws NotFoundException
     * @throws Exception
     */
    function __construct(?string $session_id = null, ?string $owner = null)
    {
        if (!is_dir(self::SESSION_PATH)) {
            mkdir(self::SESSION_PATH);
        }
        if ($session_id === null) {
            $this->createSession($owner);
        } else {
            $this->session_id = $session_id;
            $this->loadSession();
        }
    }

    private function getSessionPath(): string
    {
        return self::SESSION_PATH . $this->session_id . '.json';
    }

    private function sessionExists(): bool
    {
        $file_path = $this->getSessionPath();
        $session_exists = is_file($file_path);
        clearstatcache(true, $file_path);
        return $session_exists;
    }

    /**
     * @throws Exception
     */
    private function generateSessionId(): void
    {
        $this->session_id = getRandomString(self::SESSION_ID_LENGTH);
        if ($this->sessionExists()) {
            if ($this->existing_session_id_counter > pow(34, self::SESSION_ID_LENGTH)) {
                throw new Exception('no more sessions can be created');
            }
            $this->existing_session_id_counter++;
            $this->generateSessionId();
        }
    }

    /**
     * @throws NotFoundException
     * @throws Exception
     */
    private function loadSession(): void
    {
        if (!$this->sessionExists()) {
            throw new NotFoundException('session does not exist');
        }
        $load = json_decode(file_get_contents($this->getSessionPath()), true);
        $this->session = new PokerSession($load);
    }

    private function saveSession(): void
    {
        //file_put_contents($this->getSessionPath(), json_encode($this->session, JSON_PRETTY_PRINT));
        file_put_contents($this->getSessionPath(), json_encode($this->session));
    }

    /**
     * @throws Exception
     */
    private function createSession(string $owner, ?string $password = null): void
    {
        $this->session = new PokerSession();
        $this->current_user_id = $this->session->addUser($owner);
        if ($password !== null) $this->getCurrentUser()->password = $password;
        $this->generateSessionId();
        $this->saveSession();
    }

    private function getCurrentUser(): object
    {
        return $this->session->users->{$this->current_user_id};
    }

    public function validateUserToken(string $token): bool
    {
        foreach (get_object_vars($this->session->users) as $key => $user) {
            if ($user->token == $token) {
                $this->current_user_id = $key;
                return true;
            }
        }
        return false;
    }

    public function validateSessionToken(string $token): bool
    {
        return $this->session->token === $token;
    }

    public function setUserPassword(string $password)
    {
        $this->getCurrentUser()->password = password_hash($password, PASSWORD_DEFAULT);
        $this->saveSession();
    }

    /**
     * @throws ForbiddenException
     */
    public function userIsOwner(): bool
    {
        if ($this->current_user_id !== $this->session->owner) {
            throw new ForbiddenException('you are not the owner');
        }
        return true;
    }

    /**
     * @throws ForbiddenException
     */
    public function setSessionPassword(string $password): void
    {
        if ($this->userIsOwner()) {
            $this->session->password = password_hash($password, PASSWORD_DEFAULT);
            $this->saveSession();
        }
    }

    /**
     * @throws ForbiddenException
     * @throws Exception
     */
    public function enterSession(string $name, ?string $user_password = null, ?string $session_password = null): void
    {
        $this->current_user_id = $this->session->getUserIdFromName($name);
        if (!$this->current_user_id) {
            if ($this->session->locked) {
                throw new ForbiddenException('session is locked, new users cannot enter');
            } else {
                $this->current_user_id = $this->session->addUser($name);
                if ($user_password !== null) $this->setUserPassword($user_password);
                $this->saveSession();
            }
        } else {
            if ($this->getCurrentUser()->password !== null &&
                !password_verify($user_password, $this->getCurrentUser()->password)) {
                throw new ForbiddenException('incorect user password');
            }
            if ($this->session->password !== null &&
                !password_verify($session_password, $this->session->password)) {
                throw new ForbiddenException('incorect session password');
            }
        }
    }

    /**
     * @throws ForbiddenException
     */
    public function validateToken(string $token): void
    {
        $token_set = str_split($token, strlen($token) / 2);
        $session_token = $token_set[0];
        $user_token = $token_set[1];
        if (!$this->validateSessionToken($session_token) || !$this->validateUserToken($user_token)) {
            throw new ForbiddenException('token is invalid');
        }
    }

    /**
     * @throws ForbiddenException
     */
    public function startVote()
    {
        if ($this->userIsOwner()) {
            $this->session->addVote();
            $this->saveSession();
        }
    }

    /**
     * @throws ForbiddenException
     * @throws NotFoundException
     */
    public function vote(string $vote_id, string $card_id): void
    {
        if (!property_exists($this->session->votes, $vote_id)) {
            throw new NotFoundException('vote not found');
        }
        $vote = $this->session->votes->{$vote_id};
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
        $this->saveSession();
    }

    /**
     * @throws ForbiddenException
     * @throws NotFoundException
     */
    public function revealVote(string $vote_id): void
    {
        if ($this->userIsOwner()) {
            if (!property_exists($this->session->votes, $vote_id)) {
                throw new NotFoundException('vote not found');
            }
            $vote = $this->session->votes->{$vote_id};
            if ($vote->revealed !== null) {
                throw new ForbiddenException('vote is already closed');
            }
            $vote->revealed = time();
            $this->saveSession();
        }
    }

    /**
     * @throws Exception
     */
    public function getSessionResponse(): object
    {
        $current_vote = $this->session->getCurrentVote();
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
                'key' => $this->session->getCurrentVoteKey(),
                'revealed' => $revealed,
                'started' => $started,
                'votes' => $votes,
                'user_voted' => $user_voted
            );
        }
        return (object)array(
            'session' => $this->session_id,
            'token' => $this->session->token . $this->getCurrentUser()->token,
            'users_id' => $this->current_user_id,
            'users' => $this->session->getUserNames(),
            'owner' => $this->session->owner,
            'card_set' => $this->session->card_set,
            'current_vote' => $current_vote_response
        );
    }

}
