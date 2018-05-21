<?php
/*
 * This file is part of the Divergence package.
 *
 * (c) Henry Paradiz <henry.paradiz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Divergence\CLI\Controllers\Commands;

use Divergence\App;
use Divergence\CLI\Command;
use Divergence\CLI\Env;

/*
 *  @package Divergence\CLI
 *  @author  Henry Paradiz <henry.paradiz@gmail.com>
 */

class Initialize {
    public static function init()
    {
        $climate = Command::getClimate();

        if(!Env::$hasComposer) {
            $climate->backgroundYellow()->black()->out("No composer.json detected.");
            $climate->backgroundYellow()->black()->out('Run composer init first!');
            return;
        }

        if(Env::$isRequired) {
            $climate->info('divergence/divergence is already in your composer.json require.');
            $climate->info('Run composer install && composer update if you have not already.');
        } else {
            $climate->info('divergence/divergence is not in your composer.json require.');
            $input = $climate->confirm('Do you want to run composer require divergence/divergence for this project?');
            $input->defaultTo('y');

            if($input->confirmed()) {
                shell_exec("composer require divergence/divergence --ansi");
                Env::getEnvironment(); // force recheck
            }
        }

        static::initDirectories();

        static::initAutoloader();

        static::initDatabase();
    }

    public static function initDirectories()
    {
        $climate = Command::getClimate();

        $freshInstall = true;

        $requiredFiles = [
            'bootstrap/app.php',
            'bootstrap/autoload.php',
            'bootstrap/router.php',
            'config/app.php',
            'config/db.php',
            'public/index.php',
            'public/.htaccess',
            'views/dwoo/design.tpl',
        ];

        foreach($requiredFiles as $file) {
            if(!file_exists(getcwd().'/'.$file)) {
                $climate->error($file.' missing.');
            } else {
                $freshInstall = false;
            }
        }

        if($freshInstall) {
            $climate->info('Looks like this is a fresh install');
            $input = $climate->confirm('Do you want to bootstrap this project with framework defaults?');
            
            $input->defaultTo('y');

            if($input->confirmed()) {
                $climate->info('Creating directories...');
                foreach($requiredFiles as $file) {
                    $source = 'vendor/divergence/divergence/'.$file;
                    $dest = getcwd().'/'.$file;
                    if(!file_exists(dirname($dest))) {
                        mkdir(dirname($dest),0777,true);
                    }
                    $climate->info($dest);
                    copy($source,$dest);
                }
                $freshInstall = false;
            }
        } else {
            $climate->info('Looks like this project has been bootstrapped.');
        }
    }

    /*
     *  
     *  Adds a PSR-4 namespace to composer.json
     *  Asks if you want to run `composer install` and runs it
     *  Runs Env::getEnvironment() once to make sure it was succesful
     */
    public static function installAutoloader()
    {
        $suggestedName = explode('/',Env::$package['name'])[1];
        $input = $climate->confirm('Do you want to create a namespace called '.$suggestedName.' mapped to directory src?');
        $input->defaultTo('y');
        if($input->confirmed()) {
            Env::$package['autoload']['psr-4'][$suggestedName."\\"] = 'src/';
            Env::setPKG(getcwd(),Env::$package);
            Env::getEnvironment();

            $input = $climate->confirm('Run composer install to register new autoloaded folder?');
            $input->defaultTo('y');
            if($input->confirmed()) {
                shell_exec('composer install --ansi');
            }
        }
    }

    /*
     *  Checks autoloader config for a namespace to use.
     *  Installs one if none found.
     *  Asks to use if only one found.
     *  TODO: prompt to select if more than 
     *  Sets Env::$namespace once it's found.
     */
    public static function initAutoloader()
    {
        $climate = Command::getClimate();

        $autoloaders = Env::$autoloaders;

        if(!count($autoloaders)) {
            $climate->info('No local autoloaded directory found!');
            static::installAutoloader();
            $autoloaders = Env::$autoloaders;
        }

        // if only one autoloader ask if we should use this to initialize
        if(count($autoloaders) === 1) {
            $key = array_keys($autoloaders)[0];
            $climate->info('Found a single autoloaded namespace: '.$key.' => loaded from ./'.$autoloaders[$key]);
            $input = $climate->confirm('Initialize at this namespace?');
            
            $input->defaultTo('y');

            if($input->confirmed()) {
                Env::$namespace = $key;
            }
        }
        elseif(count($autoloaders) > 1) {
            // prompt: found count($autoloaders) autoloader configs. Which one is your namespace?
        }
    }

    public static function initDatabase()
    {
        $climate = Command::getClimate();
        App::init(getcwd());
        error_reporting(E_ALL ^E_WARNING ^E_NOTICE); // fix error reporting cause App::init acts like it's in production
        $config = App::config('db');
        
        $defaults = require getcwd().'/vendor/divergence/divergence/config/db.php';
        
        foreach($config as $label=>$dbconf) {
            if($dbconf === $defaults[$label]) {
                if(in_array($label,['mysql','dev-mysql'])) {
                    $climate->info(sprintf('Detected default database config %s',$label));
                    static::databaseConfigBuilder($label,$defaults[$label]);
                }
            }
        }
    }

    public static function databaseConfigBuilder($label,$defaults)
    {
        $climate = Command::getClimate();
        $input = $climate->inline('Config database config '.$label.'?');
        $input->defaultTo('y');
        $climate->bold('Configuring '.$label);
        $new = [];
        if($input->confirmed()) {
            $suggestedName = explode('/',Env::$package['name'])[1];

            $input = $climate->input(sprintf('Hostname (You can also provide a socket) [<bold>%s</bold>]',$defaults['host']));
            $input->defaultTo($defaults['host']);
            $new['host'] = $input->prompt();
            $input = $climate->input(sprintf('Database name [<bold>%s</bold>]',$suggestedName));
            $input->defaultTo($suggestedName);
            $new['database'] = $input->prompt();
            $input = $climate->input(sprintf('Database username [<bold>%s</bold>]',$suggestedName));
            $input->defaultTo($suggestedName);
            $new['username'] = $input->prompt();
            $input = $climate->input('Database password (leave blank to auto-generate)');
            $new['password'] = $input->prompt();
        }

        dump($new);
    }
}