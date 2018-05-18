<?php
namespace Divergence\CLI\Controllers;

abstract class CommandLineHandler
{
    abstract public static function handle();

    public static $_args;

    protected static function setArgs($path = null)
    {
        if(!static::$_args) {
            static::$_args = $_SERVER['argv'];
        }
    }
    
    protected static function peekArgs()
    {
        if (!isset(static::$_args)) {
            static::setArgs();
        }
        return count(static::$_args) ? static::$_args[0] : false;
    }

    protected static function shiftArgs()
    {
        if (!isset(static::$_args)) {
            static::setArgs();
        }
        return array_shift(static::$_args);
    }

    protected static function getArgs()
    {
        if (!isset(static::$_args)) {
            static::setArgs();
        }
        return static::$_args;
    }
    
    protected static function unshiftArgs($string)
    {
        if (!isset(static::$_args)) {
            static::setArgs();
        }
        return array_unshift(static::$_args, $string);
    }
}