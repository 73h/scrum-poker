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
        $this->headers = array_change_key_case(getallheaders(), CASE_LOWER);
        $this->loadPayload();
    }

    private function loadPayload(): void
    {
        $input_data = file_get_contents('php://input');
        if ($input_data != '') {
            $this->data = json_decode($input_data, false);
            if (json_last_error() > 0)
                $this->sendBadRequest(detail: json_last_error_msg());
        }
    }

    private function getHeader(string $key): ?string
    {
        if (array_key_exists($key, $this->headers)) {
            return $this->headers[$key];
        }
        return null;
    }

    private function validatePayload(\stdClass $data, array $required_structure, string $nested_key = ''): void
    {
        foreach ($required_structure as $value) {
            $temp_nested_key = $nested_key;
            if ($value instanceof Structure) {
                if (!property_exists($data, $value->name)) {
                    if ($value->required) {
                        $temp_nested_key .= $value->name;
                        $this->sendBadRequest(detail: $temp_nested_key . ' attribute is missing');
                    }
                } else {
                    if ($value->type == 'string' && $value->max_length !== null) {
                        if (strlen($data->{$value->name}) > $value->max_length) {
                            $temp_nested_key .= $value->name;
                            $this->sendBadRequest(
                                detail: 'field ' .
                                $temp_nested_key .
                                ' exceeds the number of ' .
                                strval($value->max_length) .
                                ' characters'
                            );

                        }
                    }
                }
                if ($value->children !== null) {
                    if (property_exists($data, $value->name)) {
                        $temp_nested_key .= $value->name . ':';
                        $this->validatePayload($data->{$value->name}, $value->children, $temp_nested_key);
                    }
                }
            }
        }
    }

    protected function get(string $route, callable $callback, ?array $required_headers = null): void
    {
        if ($this->method == 'GET' && $this->data == null) {
            $this->route($route, 'GET', $callback, required_headers: $required_headers);
        }
    }

    protected function post(string $route, callable $callback, array $payload_structure, ?array $required_headers = null): void
    {
        if ($this->method == 'POST') {
            $this->route($route, 'POST', $callback, $payload_structure, $required_headers);
        }
    }

    protected function patch(string $route, callable $callback, array $payload_structure, ?array $required_headers = null): void
    {
        if ($this->method == 'PATCH') {
            $this->route($route, 'PATCH', $callback, $payload_structure, $required_headers);
        }
    }

    private function route(string $route, string $method, callable $callback, ?array $payload_structure = null, ?array $required_headers = null): void
    {
        $route = '^' . str_replace('/', '\/', $route) . '$';
        $pattern = '/' . preg_replace('/\<[a-z_]+\>/i', '([a-z0-9]*)', $route) . '/i';
        preg_match_all($pattern, $this->route, $matches, PREG_SET_ORDER);
        if (count($matches) > 0 && $this->method === $method) {
            if ($payload_structure !== null && count($payload_structure) > 0 && $this->data === null) {
                $this->sendBadRequest(detail: 'payload is empty');
            }
            $parameters = array_filter($matches[0], function ($value, $key) {
                if ($key > 0) return $value;
            }, ARRAY_FILTER_USE_BOTH);
            if ($payload_structure !== null) {
                if (count($payload_structure) > 0) {
                    $this->validatePayload($this->data, $payload_structure);
                }
                array_push($parameters, $this->data);
            }
            if ($required_headers !== null) {
                $headers = (object)array();
                foreach ($required_headers as $required_header) {
                    $header = $this->getHeader($required_header);
                    if ($header === null) $this->sendBadRequest(detail: $required_header . ' header is missing');
                    $headers->$required_header = $header;
                }
                array_push($parameters, $headers);
            }
            call_user_func_array($callback, $parameters);
        }
    }

    private function sendResponse(int $status_code, object|array|string|int|null $response): void
    {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code($status_code);
        exit(json_encode($response));
    }

    protected function sendOk(string $message): void
    {
        $this->sendResponse(200, (object)array(
            'title' => 'Ok',
            'detail' => $message
        ));
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

}
