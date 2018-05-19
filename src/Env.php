<?php
namespace Divergence\CLI;

class Env {
    public static $me; // the command used to launch this binary
    public static $self; // json data from composer.json for \Divergence\Cli
    public static $package; // json data from composer.json for the folder from where you launched this binary

    public static $hasComposer = false;
    public static $isRequired = false;
    public static $isRequireDev = false;
    public static $namespace = null;

    public static function getPKG($path) {
        while(!file_exists($path.'/composer.json')) {
            $path = dirname($path);
            if($path == '/') {
                return false; // no composer file
            }
        }
        if(file_exists($path.'/composer.json')) {
            return json_decode(file_get_contents($path.'/composer.json'),true);
        }
    }

    public static function getEnvironment() {
        static::$self = static::getPKG(__DIR__); // this gets the composer.json info for Divergence\CLI no matter where you ran the binary from
        static::$package = static::getPKG(getcwd()); // this gets the composer.json info for the directory from which you ran the binary

        if(static::$package) {
            static::$hasComposer = true;
        }
        
        if(in_array('divergence/divergence',array_keys(static::$package['require']))) {
            static::$isRequired = true;
        } 
        if(in_array('divergence/divergence',array_keys(static::$package['require-dev']))) {
            static::$isRequireDev = true;
        }

        if(!static::$package['autoload']) {
            static::$namespace = null;
        } else {
            static::$autoloaders = static::getAutoloaders();
        }
    }

    public static function getAutoloaders() {
        $autoloaders = [];
        if(static::$package['autoload']['psr-4']) {
            $autoloaders = array_merge($autoloaders,Env::$package['autoload']['psr-4']);
        }
        if(static::$package['autoload']['psr-0']) {
            $autoloaders = array_merge($autoloaders,Env::$package['autoload']['psr-4']);
        }
        return $autoloaders;
    }
}