<?php

namespace src\views;

use src\api\Statistics;

class View
{

    private array $templates;
    private string $template;

    public function __construct(array $templates)
    {
        $this->templates = $templates;
        $this->loadTemplates();
    }

    private function loadTemplates()
    {
        $this->template = '';
        foreach ($this->templates as $template) {
            $this->template .= file_get_contents('../src/templates/' . $template . '.html');
        }
    }

    private function getAssetsVersion(): string
    {
        return '?v3';
    }

    private function getJavaScriptTag(string $file): string
    {
        return '<script type="text/javascript" src="/assets/scripts/' . $file . '.js' . $this->getAssetsVersion() . '"></script>';
    }

    protected function renderHtml(array $variables, array $script_files = [])
    {
        foreach ($variables as $key => $value) {
            $this->template = str_replace('{{' . $key . '}}', $value, $this->template);
        }
        $script_tags = '';
        foreach ($script_files as $file) {
            $script_tags .= $this->getJavaScriptTag($file);
        }
        $this->template = str_replace('{{script_tags}}', $script_tags, $this->template);
        $this->template = str_replace('{{assets_version}}', $this->getAssetsVersion(), $this->template);
        exit($this->template);
    }

    protected function getBaseUri(): string
    {
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' or $_SERVER['SERVER_PORT'] == 443) $protocol = 'https://';
        else $protocol = 'http://';
        return $protocol . $_SERVER['SERVER_NAME'] . '/';
    }

    protected function getBaseVariables(): array
    {
        $stats = new Statistics();
        return array(
            'title' => 'online planning poker',
            'subtitle' => 'for scrum teams.',
            'description' => 'online planning poker for scrum teams',
            'author' => 'Heiko Schmidt',
            'e-mail' => 'info[at]3doo.de',
            'date' => '2021-09-20',
            'keywords' => 'online, planning, poker, planning poker, online planning poker, teams, scrum',
            'sessions' => $stats->getSessions(),
            'votes' => $stats->getVotes()
        );
    }

}