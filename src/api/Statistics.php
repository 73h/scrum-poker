<?php

namespace src\api;

class Statistics
{

    const STATS_BASE_PATH = '../stats/';
    private string $statistics_path = self::STATS_BASE_PATH . 'statistics.json';
    private ?object $statistics = null;

    function __construct()
    {
        if (!is_dir(self::STATS_BASE_PATH)) {
            mkdir(self::STATS_BASE_PATH);
        }
        if (!is_file($this->statistics_path)) {
            file_put_contents($this->statistics_path, '{"sessions":0,"votes":0}');
        }
        $this->load();
    }

    private function load()
    {
        $this->statistics = json_decode(file_get_contents($this->statistics_path));
    }

    private function save()
    {
        file_put_contents($this->statistics_path, json_encode($this->statistics));
    }

    public function addSession()
    {
        $this->statistics->sessions++;
        $this->save();
    }

    public function addVote()
    {
        $this->statistics->votes++;
        $this->save();
    }

    public function getSessions(): int
    {
        return $this->statistics->sessions;
    }

    public function getVotes(): int
    {
        return $this->statistics->votes;
    }

}
