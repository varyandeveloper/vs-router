<?php

namespace VS\Router\RouteItem;

use VS\Router\MiddlewareInterface;

/**
 * Interface RouteItemInterface
 * @package VS\Router
 * @author Varazdat Stepanyan
 */
interface RouteItemInterface
{
    /**
     * RouteItemInterface constructor.
     * @param string $ctrl
     * @param string $method
     * @param array $params
     */
    public function __construct(string $ctrl, string $method, array $params);

    /**
     * @return null|string
     */
    public function getNamespace(): ?string;

    /**
     * @return string
     */
    public function getController(): string;

    /**
     * @return string
     */
    public function getMethodName(): string;

    /**
     * @return array
     */
    public function getParams(): array;

    /**
     * @return MiddlewareInterface[]
     */
    public function getMiddleware(): array;
}