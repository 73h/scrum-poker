<?php

namespace src\views;

class Index extends View
{

    public function __construct()
    {
        parent::__construct(['header', 'index', 'footer']);
        $this->render();
    }

    private function render()
    {
        $variables = array(
            'title' => 'online planning poker',
            'description' => 'online planning poker for scrum teams',
            'author' => 'Heiko Schmidt',
            'e-mail' => 'info[at]3doo.de',
            'date' => '2021-09-20',
            'keywords' => 'online, planning, poker, planning poker, online planning poker, teams, scrum',
            'url' => 'https://' . $_SERVER['HTTP_HOST'] . '/index.html'
        );
        parent::renderHtml($variables);
    }

}