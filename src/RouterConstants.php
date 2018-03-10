<?php

namespace VS\Router;

use Psr\Log\InvalidArgumentException;

/**
 * Class RouterConstants
 * @package VS\Router
 * @author Varazdat Stepanyan
 */
class RouterConstants
{
    const INVALID_ARRAY_CODE = 1;
    const INVALID_CALLABLE_CODE = 2;
    const INVALID_ALIAS_CODE = 3;
    const INVALID_ROUTE_CODE = 4;

    const INVALID_ARRAY_MESSAGE = 'Array structured route should have 0 index as controller name or controller => controllerName pairs.';
    const INVALID_CALLABLE_MESSAGE = 'Callable structured array response should return string like NameController@NameMethod or Array.';
    const INVALID_ALIAS_MESSAGE = 'The route alias %s not found.';
    const INVALID_ROUTE_MESSAGE = 'The route %s not found.';

    const DYNAMIC_ARGUMENT_DETECTION_KEY = '(';
    const NUMBER_ARGUMENT_ALIAS = '(n)';
    const STRING_ARGUMENT_ALIAS = '(s)';

    const DYNAMIC_ARGUMENT_REGEX = '/\((.*?)\)/';
    const NUMBER_ARGUMENT_REGEX = '/[0-9]/';
    const STRING_ARGUMENT_REGEX = '/[A-Za-z0-9]/';

    protected const DEFAULT_LANGUAGE = 'en';
    protected const MESSAGES = [
        self::DEFAULT_LANGUAGE => [
            self::INVALID_ARRAY_CODE => self::INVALID_ARRAY_MESSAGE,
            self::INVALID_CALLABLE_CODE => self::INVALID_CALLABLE_MESSAGE,
            self::INVALID_ALIAS_CODE => self::INVALID_ALIAS_MESSAGE,
            self::INVALID_ROUTE_CODE => self::INVALID_ROUTE_MESSAGE,
        ]
    ];
    protected const ALLOWED_METHODS = [
        'GET',
        'POST',
        'PUT',
        'PATCH',
        'DELETE',
    ];

    protected const ARGUMENT_ALIASES = [
        self::NUMBER_ARGUMENT_ALIAS => self::NUMBER_ARGUMENT_REGEX,
        self::STRING_ARGUMENT_ALIAS => self::STRING_ARGUMENT_REGEX,
    ];

    /**
     * @var array $messages
     */
    protected static $messages = [];
    /**
     * @var array $argumentAliases
     */
    protected static $argumentAliases = [];
    /**
     * @var string $dynamicArgumentDetectionKey
     */
    protected static $dynamicArgumentDetectionKey;
    /**
     * @var string $dynamicArgumentRegex
     */
    protected static $dynamicArgumentRegex;
    /**
     * @var array $allowedMethods
     */
    protected static $allowedMethods = [];

    /**
     * @param array $messages
     * @param string|null $lang
     */
    public static function setMessages(array $messages, string $lang = null): void
    {
        if (null !== $lang) {
            self::$messages[$lang] = $messages;
        } else {
            self::$messages = $messages;
        }
    }

    /**
     * @param int $code
     * @param string $lang
     * @return string
     */
    public static function getMessage(int $code, string $lang = self::DEFAULT_LANGUAGE): string
    {
        $message = self::$messages[$lang][$code] ?? self::MESSAGES[self::DEFAULT_LANGUAGE][$code] ?? false;

        if (!$message) {
            throw new InvalidArgumentException(sprintf(
                'Router message not found in %s',
                __CLASS__
            ));
        }

        return $message;
    }

    /**
     * @param array $argumentAliases
     */
    public static function setArgumentAliases(array $argumentAliases): void
    {
        self::$argumentAliases = $argumentAliases;
    }

    /**
     * @param string $alias
     * @return string
     */
    public static function getArgumentAlias(string $alias): string
    {
        $argument = self::$argumentAliases[$alias] ?? self::ARGUMENT_ALIASES[$alias] ?? false;

        if (!$argument) {
            throw new InvalidArgumentException(sprintf(
                'Alias with code %s not register in %s',
                $alias,
                __CLASS__
            ));
        }

        return $argument;
    }

    /**
     * @param string $dynamicArgumentDetectionKey
     */
    public static function setDynamicArgumentDetectionKey(string $dynamicArgumentDetectionKey): void
    {
        self::$dynamicArgumentDetectionKey = $dynamicArgumentDetectionKey;
    }

    /**
     * @return string
     */
    public static function getDynamicArgumentDetectionKey(): string
    {
        return self::$dynamicArgumentDetectionKey ?? self::DYNAMIC_ARGUMENT_DETECTION_KEY;
    }

    /**
     * @param string $dynamicArgumentRegex
     */
    public static function setDynamicArgumentRegex(string $dynamicArgumentRegex): void
    {
        self::$dynamicArgumentRegex = $dynamicArgumentRegex;
    }

    /**
     * @return string
     */
    public static function getDynamicArgumentRegex(): string
    {
        return self::$dynamicArgumentRegex ?? self::DYNAMIC_ARGUMENT_REGEX;
    }

    /**
     * @param string $method
     * @return bool
     */
    public static function isMethodAllowed(string $method): bool
    {
        if (!count(self::$allowedMethods)) {
            self::$allowedMethods = self::ALLOWED_METHODS;
        }

        return array_search(strtoupper($method), self::$allowedMethods) !== false;
    }
}