<?php

namespace src\views;

use src\api\Statistics;

class Index extends View
{

    public function __construct()
    {
        parent::__construct(['header', 'index', 'footer']);
        $this->render();
    }

    private function render()
    {
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' or $_SERVER['SERVER_PORT'] == 443) $protocol = 'https://';
        else $protocol = 'http://';
        $uri = $protocol . $_SERVER['SERVER_NAME'] . '/' . $_GET['session'];
        if (isset($_GET['session'])) {
            $image_property = 'https://chart.googleapis.com/chart?cht=qr&choe=UTF-8&chld=L|0&chs=250x131&chl=' . $uri;
        } else {
            $image_property = '/assets/images/poker.png';
        }
        $stats = new Statistics();
        $variables = array(
            'title' => 'online planning poker',
            'description' => 'online planning poker for scrum teams',
            'author' => 'Heiko Schmidt',
            'e-mail' => 'info[at]3doo.de',
            'date' => '2021-09-20',
            'keywords' => 'online, planning, poker, planning poker, online planning poker, teams, scrum',
            'url' => $uri,
            'image_property' => $image_property,
            'sessions' => $stats->getSessions(),
            'votes' => $stats->getVotes()
        );
        parent::renderHtml($variables);
    }

}