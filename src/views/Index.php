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
        $uri = parent::getBaseUri() . ($_GET['session'] ?? '');
        if (isset($_GET['session'])) {
            $image_property = 'https://api.qrserver.com/v1/create-qr-code/?size=250x131&data=' . $uri;
        } else {
            $image_property = '/assets/images/poker.png';
        }
        $variables = parent::getBaseVariables();
        $variables["url"] = $uri;
        $variables["image_property"] = $image_property;
        $script_tags = ['main', 'poker'];
        parent::renderHtml($variables, $script_tags);
    }

}