<?php
/*
 * This file is part of the Divergence package.
 *
 * (c) Henry Paradiz <henry.paradiz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Divergence\CLI\Controllers;

abstract class CommandLineHandler
{
    public static $_args;
    abstract public static function handle();

    protected static function setArgs($path = null)
    {
        if (!static::$_args) {
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
