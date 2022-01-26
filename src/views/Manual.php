<?php

namespace src\views;

class Manual extends View
{

    public function __construct()
    {
        parent::__construct(['header', 'manual', 'footer']);
        $this->render();
    }

    private function render()
    {
        $variables = parent::getBaseVariables();
        $variables["url"] = parent::getBaseUri();
        $variables["subtitle"] = 'How to use the poker tool.';
        $script_tags = ['main', 'manual'];
        parent::renderHtml($variables, $script_tags);
    }

}