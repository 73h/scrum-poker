<?php

namespace src\views;

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

    protected function renderHtml(array $variables)
    {
        foreach ($variables as $key => $value) {
            $this->template = str_replace('{{' . $key . '}}', $value, $this->template);
        };
        exit($this->template);
    }

}