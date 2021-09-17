<?php

namespace src\api;

class Request
{

    private string $route;
    protected ?\stdClass $data = null;
    protected array $headers;
    private string $method;

    function __construct()
    {
        $this->route = $_GET['api'];
        $this->method = $_SERVER['REQUEST_METHOD'];
        $this->headers = getallheaders();
        print_r($this->headers);
        $this->loadInputData();
    }

    private function loadInputData()
    {
        $input_data = file_get_contents('php://input');
        if ($input_data != '') {
            $this->data = json_decode($input_data, false);
            if (json_last_error() > 0)
                $this->sendBadRequest(detail: json_last_error_msg());
        }
    }

    protected function get(string $route, callable $callback): void
    {
        if ($this->method == 'GET' && $this->data == null)
            $this->route($route, 'GET', $callback);
    }

    protected function post(string $route, array $required_fields, callable $callback): void
    {
        if ($this->method == 'POST') {
            if ($this->data !== null) {
                foreach ($required_fields as $required_field) {
                    if (!property_exists($this->data, $required_field)) {
                        $this->sendBadRequest(detail: $required_field . ' attribute is missing');
                    }
                }
                $this->route($route, 'POST', $callback);
            } else {
                $this->sendBadRequest(detail: 'post is empty');
            }
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

    private function sendResponse(int $status_code, object|array|string|int|null $response): void
    {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code($status_code);
        exit(json_encode($response));
    }

    protected function sendSuccess(mixed $response): void
    {
        $this->sendResponse(200, $response);
    }

    protected function sendCreated(mixed $response): void
    {
        $this->sendResponse(201, $response);
    }

    protected function sendError(int $status_code, string $message, $detail = ''): void
    {
        $response = (object)array(
            'title' => $message,
            'detail' => $detail
        );
        $this->sendResponse($status_code, $response);
    }

    protected function sendNotFound(string $message = 'Not Found', $detail = ''): void
    {
        $this->sendError(404, $message, $detail);
    }

    protected function sendBadRequest(string $message = 'Bad Request', $detail = ''): void
    {
        $this->sendError(400, $message, $detail);
    }

    protected function sendForbidden(string $message = 'Forbidden', $detail = ''): void
    {
        $this->sendError(403, $message, $detail);
    }

    protected function sendInternalServerError(string $message = 'Internal Server Error', $detail = ''): void
    {
        $this->sendError(500, $message, $detail);
    }

    protected function getHeader(string $key): ?string
    {
        if (array_key_exists($key, $this->headers)) {
            return $this->headers[$key];
        }
        return null;
    }

}
