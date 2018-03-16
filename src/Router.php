<?php

namespace VS\Router;

use VS\General\DIFactory;
use VS\General\Singleton\{
    SingletonInterface, SingletonTrait
};

/**
 * Class Router
 * @package VS\Router
 * @author Varazdat Stepanyan
 */
class Router implements RouterInterface, SingletonInterface
{
    use SingletonTrait;

    /**
     * @var string $requestMethod
     */
    protected $requestMethod;
    /**
     * @var string $url
     */
    protected $url;
    /**
     * @var array $prefixes
     */
    private $prefixes = [];
    /**
     * @var array $namespaces
     */
    private $namespaces = [];
    /**
     * @var array $routeList
     */
    private $routeList = [];
    /**
     * @var string $controller
     */
    private $controller;
    /**
     * @var string $controller
     */
    private $method = 'index';
    /**
     * @var array $params
     */
    private $params = [];
    /**
     * @var array $aliases
     */
    private $aliases = [];
    /**
     * @var string $lastUsedPattern
     */
    private $lastUsedPattern;
    /**
     * @var RouteItem $routeItem
     */
    private $routeItem;

    /**
     * Router constructor.
     * @param string $url
     * @param string $requestMethod
     */
    private function __construct(string $url, string $requestMethod)
    {
        $this->url = $url;
        $this->requestMethod = $requestMethod;
    }

    /**
     * @param string $alias
     * @return RouterInterface
     */
    public function as(string $alias): RouterInterface
    {
        if (null !== $alias) {
            $this->aliases[$alias] = $this->lastUsedPattern;
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
            $argument = sprintf('/%s', RouterConstants::NUMBER_ARGUMENT_ALIAS);
            $router->get('/', "$controller.index")
                ->post('/', "$controller.store")
                ->get('/create', "$controller.create")
                ->put($argument, "$controller.update")
                ->patch($argument, "$controller.update")
                ->get($argument, "$controller.show")
                ->get("/{$argument}/edit", "$controller.edit")
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
        $this->prefix($prefix, function (RouterInterface $router) use ($controller, $namespace) {
            $router->namespace((string)$namespace, function (RouterInterface $router) use ($controller) {
                $router->get('/login', "$controller.login")->as('login');
                $router->post('/login', "$controller.login")->as('post.login');
                $router->get('/register', "$controller.register")->as('register');
                $router->post('/register', "$controller.register")->as('post.register');
                $router->get('/logout', "$controller.logout")->as('logout');
            });
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
        if (empty($this->aliases[$alias])) {
            throw new RouterException(sprintf(
                RouterConstants::getMessage(RouterConstants::INVALID_ALIAS_CODE),
                $alias
            ));
        }
        $result = $this->aliases[$alias];
        $count = count($params);
        $patterns = [];

        while ($count--) {
            $patterns[] = RouterConstants::DYNAMIC_ARGUMENT_REGEX;
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
        return $this->routeList;
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

        if (!empty($this->namespaces)) {
            $destination = implode('\\', $this->namespaces) . '\\' . $destination;
        }

        $this->routeList[$method][$piecesCount][$pattern] = $destination;
        $this->lastUsedPattern = $pattern;
        return $this;
    }

    /**
     * @return void
     */
    public function reset()
    {
        $this->routeList = [];
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
    private function getCurrent(array $alias = null): RouteItem
    {
        if (null === $alias) {
            $currentUrl = $this->getResolvedUrl();
            $piecesCount = $this->getPiecesCount($currentUrl);
            $method = $this->requestMethod;
        } else {
            $currentUrl = $alias['pattern'];
            $piecesCount = $alias['piecesCount'];
            $method = $alias['method'];
        }

        $this->makeSureRouteListIsNotEmpty($currentUrl);
        $this->checkMinimumRequirements($method, $piecesCount);

        if (empty($this->routeList[$method][$piecesCount][$currentUrl])) {
            return $this->advancedRoute($currentUrl, $this->routeList[$method][$piecesCount]);
        }

        return $this->parseValue($this->routeList[$method][$piecesCount][$currentUrl]);
    }

    /**
     * @param string $currentUrl
     * @param array $routeList
     * @return RouteItem
     * @throws RouterException
     * @throws \Throwable
     */
    private function advancedRoute(string $currentUrl, array $routeList): RouteItem
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
            }
        }
        ksort($matchPieces);

        if (!count($params)) {
            throw new RouterException(sprintf(
                RouterConstants::getMessage(RouterConstants::INVALID_ROUTE_CODE),
                $currentUrl
            ));
        }

        $theKey = '/' . implode('/', $matchPieces);

        return $this->parseValue($routeList[$theKey], $params);
    }

    /**
     * @param $activeRoute
     * @param array $args
     * @return RouteItem
     * @throws RouterException
     */
    private function parseValue($activeRoute, array $args = []): RouteItem
    {
        $this->params = $args;
        $activeRoute = $this->resolveDestination($activeRoute);

        if (is_object($activeRoute)) {
            $this->controller = $activeRoute;
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
            $this->params
        );

        return $this->routeItem;
    }

    /**
     * @param $activeRoute
     * @return mixed|string
     * @throws RouterException
     */
    private function resolveDestination($activeRoute)
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
    private function resolveArrayRoute(array $activeRoute): string
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
    private function makeSureRouteListIsNotEmpty(string $currentUrl)
    {
        if (empty($this->routeList)) {
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
    private function checkMinimumRequirements(string $method, int $piecesCount)
    {
        if (empty($this->routeList[$method][$piecesCount])) {
            throw new RouterException('Route not found');
        }
    }

    /**
     * @return string
     */
    private function getResolvedUrl(): string
    {
        $currentUrl = $this->url;
        $removePrefixFromUrl = RouterConstants::getSegmentsToAvoidAsString();
        if (!empty($removePrefixFromUrl)) {
            $currentUrl = substr_replace($currentUrl, '', strpos($currentUrl, $removePrefixFromUrl), strlen($removePrefixFromUrl));
            $currentUrl = str_replace('//', '/', $currentUrl);
        }

        return $currentUrl;
    }
}
