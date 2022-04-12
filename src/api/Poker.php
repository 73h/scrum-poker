<?php

namespace src\api;

use DateTime;
use Exception;
use src\api\exceptions\ForbiddenException;
use src\api\exceptions\NotFoundException;

class Poker
{

    const SESSION_BASE_PATH = '../sessions/';
    private ?string $session_path = null;
    const SESSION_ID_LENGTH = 6;
    private Session $session;
    private ?string $session_id = null;
    private int $existing_session_id_counter = 0;
    private string $current_user_id;

    /**
     * @throws NotFoundException
     * @throws Exception
     */
    function __construct(?string $session_id = null, ?string $owner = null, ?string $card_set = null)
    {
        if (!is_dir(self::SESSION_BASE_PATH)) {
            mkdir(self::SESSION_BASE_PATH);
        }
        if ($session_id === null) {
            $this->createSession($owner, $card_set);
        } else {
            $this->session_id = $session_id;
            $this->loadSession();
        }
    }


    /**
     * @throws Exception
     */
    private function makeSessionBasePath(): void
    {
        if ($this->session_id === null) {
            $this->generateSessionId();
        }
        if ($this->session_path === null) {
            $this->session_path = self::SESSION_BASE_PATH . $this->session_id . '/';
            if (!is_dir($this->session_path)) {
                mkdir($this->session_path);
            }
        }
    }

    /**
     * @throws Exception
     */
    private function getSessionPath(): string
    {
        $this->makeSessionBasePath();
        return $this->session_path . 'session.json';
    }

    /**
     * @throws Exception
     */
    private function getUserSessionAlivePath($user_is): string
    {
        $this->makeSessionBasePath();
        return $this->session_path . $user_is . '.alive';
    }

    /**
     * @throws Exception
     */
    private function sessionExists(): bool
    {
        if ($this->session_id === null) return false;
        $session_exists = is_dir(self::SESSION_BASE_PATH . $this->session_id);
        clearstatcache(true, self::SESSION_BASE_PATH . $this->session_id);
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
        $this->session = new Session($load);
    }

    /**
     * @throws Exception
     */
    private function saveSession(): void
    {
        file_put_contents($this->getSessionPath(), json_encode($this->session));
    }

    /**
     * @throws Exception
     */
    private function saveUserSessionAlive(): void
    {
        file_put_contents($this->getUserSessionAlivePath($this->current_user_id), time());
    }

    /**
     * @throws Exception
     */
    private function createSession(?string $owner = null, ?string $card_set = null): void
    {
        $this->session = new Session(card_set: $card_set);
        if ($owner != null) {
            $this->current_user_id = $this->session->addUser($owner);
            $this->saveUserSessionAlive();
        }
        $this->saveSession();
        $stats = new Statistics();
        $stats->addSession();
    }

    private function getCurrentUser(): object
    {
        return $this->session->users->{$this->current_user_id};
    }

    /**
     * @throws Exception
     */
    private function getUserNames(): object
    {
        $users = (object)[];
        $current_vote = $this->session->getCurrentVote();
        foreach (get_object_vars($this->session->users) as $key => $user) {
            $user_alive_time = file_get_contents($this->getUserSessionAlivePath($key));
            $user_voted = property_exists($current_vote->votes, $key);
            $user_vote = null;
            if (property_exists($current_vote->votes, $key) && ($current_vote->uncovered || $key == $this->current_user_id)) {
                $user_vote = $current_vote->votes->{$key}->card;
            }
            $users->{$key} = (object)[
                'user_id' => strval($key),
                'name' => $user->name,
                'alive' => (time() - $user_alive_time < 10),
                'voted' => $user_voted,
                'vote' => $user_vote,
                'robo_icon' => property_exists($user, 'icon') ? $user->icon : null
            ];
        }
        return $users;
    }

    /**
     * @throws Exception
     */
    public function validateUserToken(string $token): bool
    {
        foreach (get_object_vars($this->session->users) as $key => $user) {
            if ($user->token == $token) {
                $this->current_user_id = $key;
                $this->saveUserSessionAlive();
                return true;
            }
        }
        return false;
    }

    public function validateSessionToken(string $token): bool
    {
        return $this->session->token === $token;
    }

    /**
     * @throws Exception
     */
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
     * @throws Exception
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
        if ($this->session->password !== null &&
            !password_verify($session_password, $this->session->password)) {
            throw new ForbiddenException('incorect session password');
        }
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
        }
        $this->saveUserSessionAlive();
    }

    /**
     * @throws ForbiddenException
     */
    public function validateToken(?string $token): void
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
     * @throws Exception
     */
    public function startVote()
    {
        if ($this->userIsOwner()) {
            $this->session->addVote();
            $this->saveSession();
            $stats = new Statistics();
            $stats->addVote();
        }
    }

    /**
     * @throws ForbiddenException
     * @throws NotFoundException
     * @throws Exception
     */
    public function vote(string $vote_id, string $card_id): void
    {
        if (!property_exists($this->session->votes, $vote_id)) {
            throw new NotFoundException('vote not found');
        }
        $vote = $this->session->votes->{$vote_id};
        if ($vote->uncovered !== null) {
            throw new ForbiddenException('vote is already closed');
        }
        $card_set = Cards::allCards()->{$vote->card_set};
        if (!property_exists($card_set, $card_id)) {
            throw new NotFoundException('card not found');
        }
        $vote->votes->{$this->current_user_id} = (object)[
            'card' => $card_id,
            'voted' => time()
        ];
        $this->saveSession();
    }

    /**
     * @throws ForbiddenException
     * @throws NotFoundException
     * @throws Exception
     */
    public function uncoverVoting(string $vote_id): void
    {
        if ($this->userIsOwner()) {
            if (!property_exists($this->session->votes, $vote_id)) {
                throw new NotFoundException('vote not found');
            }
            $vote = $this->session->votes->{$vote_id};
            if ($vote->uncovered !== null) {
                throw new ForbiddenException('vote is already closed');
            }
            $vote->uncovered = time();
            $this->saveSession();
        }
    }

    public function getBasicSessionResponse(): object
    {
        return (object)[
            'session' => $this->session_id,
            'has_password' => $this->session->password !== null
        ];
    }

    /**
     * @throws Exception
     */
    public function getSessionResponse(): object
    {
        $current_vote = $this->session->getCurrentVote();
        $current_vote_response = null;
        if ($current_vote !== null) {
            $started = (new DateTime)->setTimestamp($current_vote->started)->format(DATE_ATOM);
            $uncovered = null;
            if (($current_vote->uncovered !== null)) {
                $uncovered = (new DateTime)->setTimestamp($current_vote->uncovered)->format(DATE_ATOM);
            }
            $current_vote_response = (object)[
                'key' => $this->session->getCurrentVoteKey(),
                'uncovered' => $uncovered,
                'started' => $started,
            ];
        }
        return (object)[
            'session' => $this->session_id,
            'token' => $this->session->token . $this->getCurrentUser()->token,
            'user_id' => $this->current_user_id,
            'users' => $this->getUserNames(),
            'owner' => $this->session->owner,
            'card_set' => $this->session->card_set,
            'current_vote' => $current_vote_response
        ];
    }

}
