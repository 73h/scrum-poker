<?php

namespace src\api;

class Slack
{

    private \stdClass $token;

    function __construct()
    {
        $this->load();
    }

    private function load()
    {
        $this->token = json_decode(file_get_contents('../slack-token.json'));
    }

    public function validateToken(string $token): bool
    {
        return str_contains($this->token->token, $token);
    }

    public function getSlackResponse(Poker $poker): object
    {
        $basicResponse = $poker->getBasicSessionResponse();
        $uri = getAppUri() . "/" . $basicResponse->session;
        return (object)[
            "parse" => "full",
            "response_type" => "in_channel",
            "text" => "have fun playing poker >> " . $uri,
            "attachments" => [
                (object)["image_url" => "https://chart.googleapis.com/chart?cht=qr&choe=UTF-8&chld=L|0&chs=250x250&chl=" . $uri]
            ],
            "unfurl_media" => true,
            "unfurl_links" => true
        ];
    }

    public function getSlackCardSetErrorResponse(): object
    {
        $test = implode(", ", array_keys((array)Cards::allCards()));
        return (object)[
            "response_type" => "ephemeral",
            "text" => "Sorry, this card set does not exist.\r\n\r\nExisting card sets: " . $test . "\r\n\r\nExample: /poker fibonacci"
        ];
    }

    public function getSlackErrorResponse(): object
    {
        $test = implode(", ", array_keys((array)Cards::allCards()));
        return (object)[
            "response_type" => "ephemeral",
            "text" => "Sorry, something went wrong."
        ];
    }

}