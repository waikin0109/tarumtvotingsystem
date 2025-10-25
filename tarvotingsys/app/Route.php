<?php
class Route
{
    private static $routes = [];

    public static function get($uri, $action)
    {
        self::$routes['GET'][] = ['uri' => $uri, 'action' => $action];
    }

    public static function post($uri, $action)
    {
        self::$routes['POST'][] = ['uri' => $uri, 'action' => $action];
    }

    public static function dispatch($requestUri, $requestMethod)
    {
        $uri = parse_url($requestUri, PHP_URL_PATH);

        if (!isset(self::$routes[$requestMethod])) {
            http_response_code(405);
            echo "405 - Method Not Allowed";
            return;
        }

        foreach (self::$routes[$requestMethod] as $route) {
            $routePattern = $route['uri'];

            // Convert route pattern to regex
            $regex = preg_replace('#\{[a-zA-Z_][a-zA-Z0-9_]*\}#', '([a-zA-Z0-9_-]+)', $routePattern);
            $regex = '#^' . $regex . '$#';

            if (preg_match($regex, $uri, $matches)) {
                array_shift($matches); // Remove full match
                [$controllerClass, $method] = $route['action'];

                if (!class_exists($controllerClass)) {
                    http_response_code(500);
                    echo "Controller class not found: $controllerClass";
                    return;
                }

                $controller = new $controllerClass();

                if (!method_exists($controller, $method)) {
                    http_response_code(500);
                    echo "Method '$method' not found in controller '$controllerClass'";
                    return;
                }

                return call_user_func_array([$controller, $method], $matches);
            }
        }

        http_response_code(404);
        echo "404 - Not Found";
    }
}
