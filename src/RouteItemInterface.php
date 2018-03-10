<?php
/**
 * Created by IntelliJ IDEA.
 * User: user
 * Date: 3/10/2018
 * Time: 9:53 AM
 */

namespace VS\Router;

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
}