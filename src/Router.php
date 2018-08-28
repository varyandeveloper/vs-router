<?php

namespace VS\Router;

use VS\General\DIFactory;
use VS\General\Exceptions\ClassNotFoundException;
use VS\Url\UrlInterface;
use VS\Request\RequestInterface;
use VS\Router\RouteItem\{
    RouteItem, RouteItemInterface
};

/**
 * Class Router
 * @package VS\Router
 * @author Varazdat Stepanyan
 */
class Router implements RouterInterface
{
    /**
     * @var RequestInterface $request
     */
    protected $request;
    /**
     * @var UrlInterface $url
     */
    protected $url;
    /**
     * @var array $routeList
     */
    protected static $routeList = [];
    /**
     * @var array $prefixes
     */
    protected $prefixes = [];
    /**
     * @var array $namespaces
     */
    protected $namespaces = [];
    /**
     * @var string $controller
     */
    protected $controller;
    /**
     * @var string $controller
     */
    protected $method = 'index';
    /**
     * @var array $params
     */
    protected $params = [];
    /**
     * @var string $lastUsedPattern
     */
    protected $lastUsedPattern;
    /**
     * @var RouterInterface $routeItem
     */
    protected $routeItem;
    /**
     * @var MiddlewareInterface[] $middleware
     */
    protected $middleware = [];
    /**
     * @var array $aliases
     */
    protected static $aliases = [];

    /**
     * Router constructor.
     * @param UrlInterface $url
     * @param RequestInterface $request
     */
    public function __construct(UrlInterface $url, RequestInterface $request)
    {
        $this->url = $url;
        $this->request = $request;
    }

    /**
     * @param string $alias
     * @return RouterInterface
     */
    public function as(string $alias): RouterInterface
    {
        if (null !== $alias) {
            static::$aliases[$alias] = $this->lastUsedPattern;
        }
        return $this;
    }

    /**
     * @param MiddlewareInterface ...$middleware
     * @return RouterInterface
     */
    public function middleware(MiddlewareInterface ...$middleware): RouterInterface
    {
        $this->middleware = $middleware;
        return $this;
    }

    /**
     * @param array $rules
     * @param callable $callback
     * @return RouterInterface
     */
    public function rules(array $rules, callable $callback): RouterInterface
    {
        if (!empty($rules['prefix'])) {
            $this->prefixes[] = $rules['prefix'];
        }

        if (!empty($rules['namespace'])) {
            $this->namespaces[] = $rules['namespace'];
        }

        if (!empty($rules['middleware'])) {
            $this->middleware[] = $rules['middleware'];
        }

        $callback($this);

        if (!empty($rules['prefix'])) {
            array_pop($this->prefixes);
        }

        if (!empty($rules['namespace'])) {
            array_pop($this->namespaces);
        }

        if (!empty($rules['middleware'])) {
            array_pop($this->middleware);
        }

        return $this;
    }

    /**
     * @param string $prefix
     * @param string $controller
     * @return RouterInterface
     */
    public function CRUD(string $prefix, string $controller): RouterInterface
    {
        $this->prefix($prefix, function (RouterInterface $router) use ($controller) {
            $argument = sprintf('\%s', RouterConstants::NUMBER_ARGUMENT_ALIAS);
            $router->get('/', "$controller.index")
                ->post('/', "$controller.store")
                ->get('/create', "$controller.create")
                ->put($argument, "$controller.update")
                ->patch($argument, "$controller.updateFew")
                ->get($argument, "$controller.show")
                ->get("{$argument}/edit", "$controller.edit")
                ->delete($argument, "$controller.destroy");
        });

        return $this;
    }

    /**
     * @param string $prefix
     * @param string $controller
     * @return RouterInterface
     */
    public function REST(string $prefix, string $controller): RouterInterface
    {
        $this->prefix($prefix, function (RouterInterface $router) use ($controller) {
            $argument = sprintf('/%s', RouterConstants::NUMBER_ARGUMENT_ALIAS);
            $router->get('/', "$controller.list")
                ->get($argument, "$controller.one")
                ->post('/', "$controller.save")
                ->put($argument, "$controller.replace")
                ->patch($argument, "$controller.replaceFew")
                ->delete($argument, "$controller.remove");
        });

        return $this;
    }

    /**
     * @param string $prefix
     * @param string $controller
     * @param string|null $namespace
     * @return RouterInterface
     */
    public function AUTH(string $prefix, string $controller, string $namespace = ''): RouterInterface
    {
        $namespace = rtrim($namespace, '\\') . '\\';
        if ($namespace === '\\') {
            $namespace = '';
        }
        $this->prefix($prefix, function (RouterInterface $router) use ($controller, $namespace) {
            $router->get('/login', "{$namespace}{$controller}.login")->as('login');
            $router->post('/login', "{$namespace}{$controller}.loginPost")->as('post.login');
            $router->get('/register', "{$namespace}{$controller}.register")->as('register');
            $router->post('/register', "{$namespace}{$controller}.registerPost")->as('post.register');
            $router->post('/logout', "{$namespace}{$controller}.logout")->as('logout');
        });

        return $this;
    }

    /**
     * @param string $alias
     * @param array $params
     * @return string
     * @throws RouterException
     */
    public function getByAlias(string $alias, array $params = []): string
    {
        if (empty(static::$aliases[$alias])) {
            throw new RouterException(sprintf(
                RouterConstants::getMessage(RouterConstants::INVALID_ALIAS_CODE),
                $alias
            ));
        }
        $result = static::$aliases[$alias];
        $count = count($params);
        $patterns = [];

        while ($count--) {
            $patterns[] = RouterConstants::ANY_ARGUMENT_REGEX;
        }

        if (!empty($params)) {
            $result = preg_replace($patterns, $params, $result, 1);
        }

        return $result;
    }

    /**
     * @param string $prefix
     * @param callable $callback
     * @return RouterInterface
     */
    public function prefix(string $prefix, callable $callback): RouterInterface
    {
        $prefix = ltrim($prefix, '/');
        if (!empty($prefix)) {
            $this->prefixes[] = $prefix;
        }
        $callback($this);
        array_pop($this->prefixes);
        return $this;
    }

    /**
     * @param string $namespace
     * @param \Closure $callback
     * @return RouterInterface
     */
    public function namespace(string $namespace, \Closure $callback): RouterInterface
    {
        if (!empty($namespace)) {
            $this->namespaces[] = $namespace;
        }
        $callback($this);
        array_pop($this->namespaces);
        return $this;
    }

    /**
     * @return array
     */
    public function getRoutes(): array
    {
        return static::$routeList;
    }

    /**
     * @return RouteItemInterface
     * @throws RouterException
     * @throws \Throwable
     */
    public function getRouteItem(): RouteItemInterface
    {
        if (!$this->routeItem) {
            $this->routeItem = $this->getCurrent();
        }

        return $this->routeItem;
    }

    /**
     * @param string $method
     * @param string $pattern
     * @param $destination
     * @return RouterInterface
     */
    public function map(string $method, string $pattern, $destination): RouterInterface
    {
        $method = strtoupper($method);
        if (!empty($this->prefixes)) {
            if ($pattern === '/') {
                $pattern = '';
            }
            $pattern = '/' . implode('/', $this->prefixes) . $pattern;
        }

        if (strpos($pattern, '/') === FALSE) {
            $pattern = '/' . $pattern;
        }

        $piecesCount = $this->getPiecesCount($pattern);

        if (!empty($this->namespaces) && is_string($destination)) {
            $destination = implode('\\', $this->namespaces) . '\\' . $destination;
        }

        static::$routeList[$method][$piecesCount][$pattern] = $destination;
        $this->lastUsedPattern = $pattern;
        return $this;
    }

    /**
     * @return void
     */
    public function reset()
    {
        static::$routeList = [];
    }

    /**
     * @param string $name
     * @param array $arguments
     * @return RouterInterface
     */
    public function __call(string $name, array $arguments): RouterInterface
    {
        if (!RouterConstants::isMethodAllowed($name)) {
            throw new \BadMethodCallException(sprintf(
                'Class %s dose not have %s method.',
                __CLASS__,
                $name
            ));
        }

        return $this->map($name, ...$arguments);
    }

    /**
     * @param $pattern
     * @return int
     */
    private function getPiecesCount($pattern): int
    {
        $piecesCount = substr_count($pattern, '/');
        return $piecesCount;
    }

    /**
     * @param array|null $alias
     * @return RouteItem
     * @throws RouterException
     * @throws \Throwable
     */
    protected function getCurrent(array $alias = null): RouteItem
    {
        if (null === $alias) {
            $currentUrl = $this->getResolvedUrl();
            $piecesCount = $this->getPiecesCount($currentUrl);
            $method = $this->request->method();
        } else {
            $currentUrl = $alias['pattern'];
            $piecesCount = $alias['piecesCount'];
            $method = $alias['method'];
        }

        $this->makeSureRouteListIsNotEmpty($currentUrl);
        $this->checkMinimumRequirements($method, $piecesCount);

        if (empty(static::$routeList[$method][$piecesCount][$currentUrl])) {
            return $this->advancedRoute($currentUrl, static::$routeList[$method][$piecesCount]);
        }

        return $this->parseValue(static::$routeList[$method][$piecesCount][$currentUrl]);
    }

    /**
     * @param string $currentUrl
     * @param array $routeList
     * @return RouteItem
     * @throws RouterException
     * @throws \Throwable
     */
    protected function advancedRoute(string $currentUrl, array $routeList): RouteItem
    {
        $matchPieces = [];
        $params = [];
        $routeKeys = array_keys($routeList);
        $urlParts = explode('/', ltrim($currentUrl, '/'));

        // get length of url segments
        $urlPartsLength = count($urlParts);
        // loop throw route keys
        foreach ($routeKeys as $key) {
            // explode each key by slash
            $keyParts = explode('/', ltrim($key, '/'));

            if (count($matchPieces) !== $urlPartsLength) {
                foreach ($keyParts as $i => $keyPart) {
                    if ($keyPart === $urlParts[$i]) {
                        $matchPieces[$i] = $urlParts[$i];
                    } elseif (strpos($keyPart, RouterConstants::getDynamicArgumentDetectionKey()) !== FALSE) {
                        try {
                            if (preg_match(RouterConstants::getArgumentAlias($keyPart), $urlParts[$i])) {
                                $matchPieces[$i] = $keyPart;
                                $params[$i] = $urlParts[$i];
                            }
                        } catch (\Throwable $exception) {
                            throw $exception;
                        }
                    } else {
                        continue;
                    }
                }
            } else {
                break;
            }
        }

        if (count($matchPieces) !== $urlPartsLength || !count($params)) {
            throw new RouterException(sprintf(
                RouterConstants::getMessage(RouterConstants::INVALID_ROUTE_CODE),
                $currentUrl
            ));
        }

        ksort($matchPieces);

        $theKey = '/' . implode('/', $matchPieces);

        return $this->parseValue($routeList[$theKey], $params);
    }

    /**
     * @param $activeRoute
     * @param array $args
     * @return RouteItem
     * @throws RouterException
     * @throws \ReflectionException
     * @throws ClassNotFoundException
     */
    protected function parseValue($activeRoute, array $args = []): RouteItem
    {
        $this->params = $args;
        $activeRoute = $this->resolveDestination($activeRoute);

        if (is_object($activeRoute)) {
            if (method_exists($activeRoute, '__toString')) {
                print $activeRoute;
                exit;
            }
            throw new \InvalidArgumentException(sprintf(
                RouterConstants::getMessage(RouterConstants::INVALID_TO_STRING_METHOD_CODE),
                get_class($activeRoute)
            ), RouterConstants::INVALID_TO_STRING_METHOD_CODE);
        } else {
            $activeRoute = str_replace(['@', '.'], '/', $activeRoute);
            $valueParts = explode('/', $activeRoute);
            $this->controller = $valueParts[0];
            $this->method = $valueParts[1] ?? 'index';
            unset($valueParts[0], $valueParts[1]);
            if (count($valueParts)) {
                $this->params = array_merge($valueParts, $args);
            }
        }

        $this->routeItem = new RouteItem (
            $this->controller,
            $this->method,
            $this->params,
            $this->middleware
        );

        $this->middleware = [];
        return $this->routeItem;
    }

    /**
     * @param $activeRoute
     * @return mixed|string
     * @throws RouterException
     * @throws \ReflectionException
     * @throws ClassNotFoundException
     */
    protected function resolveDestination($activeRoute)
    {
        if (is_string($activeRoute)) {
            return $activeRoute;
        }

        if (is_callable($activeRoute)) {
            $result = DIFactory::injectFunction($activeRoute, ...$this->params);

            if (is_array($result)) {
                return $this->resolveArrayRoute($result);
            }

            return $result;

        } elseif (is_array($activeRoute)) {
            return $this->resolveArrayRoute($activeRoute);
        }

        throw new RouterException(RouterConstants::getMessage(RouterConstants::INVALID_CALLABLE_CODE));
    }

    /**
     * @param array $activeRoute
     * @return string
     * @throws RouterException
     */
    protected function resolveArrayRoute(array $activeRoute): string
    {
        if (empty($activeRoute['controller']) && empty($activeRoute[0])) {
            throw new RouterException(RouterConstants::getMessage(RouterConstants::INVALID_ARRAY_CODE));
        }
        $route = $activeRoute['controller'] ?? $activeRoute[0];
        $route .= '@';
        $route .= $activeRoute['method'] ?? 'index';

        return $route;
    }

    /**
     * @param string $currentUrl
     * @throws RouterException
     */
    protected function makeSureRouteListIsNotEmpty(string $currentUrl)
    {
        if (empty(static::$routeList)) {
            throw new RouterException(sprintf(
                RouterConstants::getMessage(RouterConstants::INVALID_ROUTE_CODE),
                $currentUrl
            ));
        }
    }

    /**
     * @param string $method
     * @param int $piecesCount
     * @throws RouterException
     */
    protected function checkMinimumRequirements(string $method, int $piecesCount)
    {
        if (empty(static::$routeList[$method][$piecesCount])) {
            throw new RouterException('Route not found');
        }
    }

    /**
     * @return string
     */
    protected function getResolvedUrl(): string
    {
        $currentUrl = $this->url->current();
        $removePrefixFromUrl = RouterConstants::getSegmentsToAvoidAsString();
        if (!empty($removePrefixFromUrl) && ($pos = strpos($currentUrl, $removePrefixFromUrl) !== false)) {
            $currentUrl = substr_replace($currentUrl, '', $pos, strlen($removePrefixFromUrl));
            $currentUrl = str_replace('//', '/', $currentUrl);
        }

        return $currentUrl ?: '/';
    }
}
