<?php

namespace src\api;

class Request
{

    private string $route;
    private $data;
    private string $method;

    function __construct()
    {
        $this->route = $_GET['api'];
        $this->data = json_decode(file_get_contents(filename: 'php://input'), associative: true);
        $this->method = $_SERVER['REQUEST_METHOD'];
    }

    private function get(string $route, callable $callback): void
    {
        $this->route($route, 'GET', $callback);
    }

    private function post(string $route, callable $callback): void
    {
        $this->route($route, 'POST', $callback);
    }

    private function route(string $route, string $method, callable $callback): void
    {
        $route = str_replace(search: '/', replace: '\/', subject: $route);
        preg_match_all(pattern: '/\<([a-z0-9_]+)\>/', subject: $route, matches: $matches_parameters, flags: PREG_PATTERN_ORDER);
        $parameter_names = array_map(function ($parameter) {
            return $parameter;
        }, $matches_parameters[1]);
        $pattern = '/' . preg_replace(pattern: '/\<[a-z0-9_]+\>/i', replacement: '([a-z0-9_-]*)', subject: $route) . '/i';
        preg_match_all(pattern: $pattern, subject: $this->route, matches: $matches, flags: PREG_SET_ORDER);
        if (count($matches) > 0 && $this->method === $method) {
            $parameters = (object)array();
            for ($i = 1; $i < count($matches[0]); $i++) {
                $key = $parameter_names[$i - 1];
                $parameters->$key = $matches[0][$i];
            }
            $callback($parameters);
        }
    }

    private function send_response(object|array|string|int|null $response): void
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(value: $response);
        exit;
    }

    public function handle_request(): string
    {
        $this->get(route: 'rooms/<room>/votes/<vote>', callback: function ($parameters) {
            // handle route
            $this->send_response($parameters);
        });
        $this->post(route: 'rooms', callback: function () {
            // handle route
            $this->send_response($this->data);
        });
        http_response_code(response_code: 404);
        return "not found";
    }

}