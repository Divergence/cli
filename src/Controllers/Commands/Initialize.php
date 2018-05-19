<?php
namespace Divergence\CLI\Controllers\Commands;

use Divergence\CLI\Command;
use Divergence\CLI\Env;

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
            $input = $climate->input('Do you want to run composer require divergence/divergence for this project? [y,n]');
            $input->accept(['y', 'yes','no','n']);

            $response = $input->prompt();
            if(in_array($response,['y','yes'])) {
                shell_exec("composer require divergence/divergence --ansi");
                Env::getEnvironment(); // force recheck
            }
        }

        static::initDirectories();

        static::initAutoloader();
    }

    public static function initAutoloader()
    {
        $climate = Command::getClimate();
        if(!Env::$namespace) {
            $climate->info('No local autoloaded directory found!');
            return;
        }

        $autoloaders = Env::$autoloaders;

        if(count($autoloaders) === 1) {
            // prompt: found 1 autoloader config. Is this your namespace?
        }
        elseif(count($autoloaders) > 1) {
            // prompt: found count($autoloaders) autoloader configs. Which one is your namespace?
        }

        if(!count($autoloaders)) {
            // prompt: do you want to create a new namespace? default: name of this package from composer.json
        }
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
            $input = $climate->input('Do you want to bootstrap this project with framework defaults? [y,n]');
            $input->accept(['y', 'yes','no','n']);

            $response = $input->prompt();
            if(in_array($response,['y','yes'])) {
                foreach($requiredFiles as $file) {
                    $source = 'vendor/divergence/divergence/'.$file;
                    $dest = getcwd().'/'.$file;
                    if(!file_exists(dirname($dest))) {
                        mkdir(dirname($dest),0777,true);
                    }
                    copy($source,$dest);
                }
                $freshInstall = false;
            }
        } else {
            $climate->info('Looks like this project has been bootstrapped.');
        }
    }
}