<?php

namespace src\api;

class Request
{

    private string $route;
    protected $data;
    private string $method;

    function __construct()
    {
        $this->route = $_GET['api'];
        $this->method = $_SERVER['REQUEST_METHOD'];
        $this->load_input_data();
    }

    private function load_input_data()
    {
        $input_data = file_get_contents('php://input');
        if ($input_data != '') {
            $this->data = json_decode($input_data, true);
            if (json_last_error() > 0)
                $this->send_400(detail: json_last_error_msg());
        }
    }

    protected function get(string $route, callable $callback): void
    {
        if ($this->method == 'GET' && $this->data == null)
            $this->route($route, 'GET', $callback);
    }

    protected function post(string $route, callable $callback): void
    {
        if ($this->method == 'POST') {
            if ($this->data !== null)
                $this->route($route, 'POST', $callback);
            else
                $this->send_400(detail: 'the post is empty');
        }
    }

    private function route(string $route, string $method, callable $callback): void
    {
        $route = '^' . str_replace('/', '\/', $route) . '$';
        $pattern = '/' . preg_replace('/\<[a-z_]+\>/i', '([a-z0-9]*)', $route) . '/i';
        preg_match_all($pattern, $this->route, $matches, PREG_SET_ORDER);
        if (count($matches) > 0 && $this->method === $method) {
            $parameters = array_filter($matches[0], function ($value, $key) {
                if ($key > 0) return $value;
            }, ARRAY_FILTER_USE_BOTH);
            if ($this->data !== null)
                array_push($parameters, $this->data);
            call_user_func_array($callback, $parameters);
        }
    }


    private function send_response(int $status_code, object|array|string|int|null $response): void
    {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code($status_code);
        exit(json_encode($response));
    }

    protected function send_success(mixed $response): void
    {
        $this->send_response(200, $response);
    }

    protected function send_error(int $status_code, string $message, $detail = ''): void
    {
        $response = (object)array(
            'title' => $message,
            'detail' => $detail
        );
        $this->send_response($status_code, $response);
    }

    protected function send_404(string $message = 'Not Found', $detail = ''): void
    {
        $this->send_error(404, $message, $detail);
    }

    protected function send_400(string $message = 'Bad Request', $detail = ''): void
    {
        $this->send_error(400, $message, $detail);
    }

}