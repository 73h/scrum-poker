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
        $script_tags = ['scheme'];
        parent::renderHtml($variables, $script_tags);
    }

}