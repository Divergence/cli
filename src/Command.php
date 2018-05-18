<?php
namespace Divergence\CLI;

use Divergence\CLI\Controllers\CommandLineHandler;
use \League\CLImate\CLImate;

class Command extends CommandLineHandler
{
    public static $me; // the command used to launch this binary
    public static $self; // json data from composer.json for \Divergence\Cli
    public static $package; // json data from composer.json for the folder from where you launched this binary
    public static $climate; // instance of \League\CLImate\CLImate;

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
    }

    public static function handle()
    {
        static::getEnvironment();

        static::$me = static::shiftArgs();

        static::$climate = new CLImate();

        switch($action = static::shiftArgs()) {
            case 'init':
                static::init();
            break;

            case 'status':
                static::status();
            break;

            case 'build':
                // Divergence\Controllers\Builder::handle();
            break;

            case '-v':
            case '--version':
                static::version();
            break;

            case '--help':
            case '-h':
            case 'help':
            default:
                static::usage();
        }
    }

    public static function init()
    {
        if(!static::$hasComposer) {
            static::$climate->backgroundYellow()->black()->out("No composer.json detected.");
            static::$climate->backgroundYellow()->black()->out('Run composer init first!');
            return;
        }

        if(static::$isRequired) {
            static::$climate->info('divergence/divergence is already in your composer.json require.');
            static::$climate->info('Run composer install && composer update if you have not already.');
        } else {
            static::$climate->info('divergence/divergence is not in your composer.json require.');
            $input = static::$climate->input('Do you want to run composer require divergence/divergence for this project? [y,n]');
            $input->accept(['y', 'yes','no','n']);

            $response = $input->prompt();
            if(in_array($response,['y','yes'])) {
                shell_exec("composer require divergence/divergence --ansi");
                static::getEnvironment(); // force recheck
            }
        }

        static::checkDirectories();

        static::checkAutoloader();
    }

    public static function checkAutoloader()
    {
        if(!static::$package['autoload']) {
            static::$climate->info('No local autoloaded directory found!');
            static::$namespace = null;
        }

        $autoloaders = [];

        if(!static::$namespace) {
            if(static::$package['autoload']['psr-4']) {
                $autoloaders = array_merge($autoloaders,static::$package['autoload']['psr-4']);
            }
            if(static::$package['autoload']['psr-0']) {
                $autoloaders = array_merge($autoloaders,static::$package['autoload']['psr-4']);
            }
        }

        dump($autoloaders);

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

    public static function checkDirectories()
    {
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
                static::$climate->error($file.' missing.');
            } else {
                $freshInstall = false;
            }
        }

        if($freshInstall) {
            static::$climate->info('Looks like this is a fresh install');
            $input = static::$climate->input('Do you want to bootstrap this project with framework defaults? [y,n]');
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
            static::$climate->info('Looks like this project has been bootstrapped.');
        }
    }

    public static function status()
    {
        if(!static::$hasComposer) {
            static::$climate->backgroundYellow()->black()->out("No composer.json detected.");
            return;
        }

        static::$climate->info(sprintf('Found %s',static::$package['name']));
        
        if(!static::$isRequired && !static::$isRequireDev) {
            static::$climate->backgroundYellow()->black()->out('Did not find divergence/divergence in composer.json require');
            static::$climate->backgroundYellow()->black()->out('Run divergence init to bootstrap your project');
            return;
        }

        if(static::$isRequired) {
            static::$climate->info('Found divergence/divergence in composer.json require');
        }

        if(static::$isRequireDev) {
            static::$climate->info('Found divergence/divergence in composer.json require-dev');
        }
        

    }

    public static function version()
    {
        static::$climate->out('Divergence Command Line Tool');
    }

    public static function usage()
    {
        static::version();
        static::$climate->out('');
        static::$climate->out(" divergence [command] [arguments]");
        static::$climate->out('');
        static::$climate->out('');
        static::$climate->bold("\tAvailable Arguments");
        static::$climate->out("\t--version, -v\t\tVersion information");
        static::$climate->out('');
        static::$climate->out("\thelp, --help, -h\tThis help information");
        static::$climate->out('');
        static::$climate->bold("\tAvailable Commands");
        static::$climate->out('');
        static::$climate->out("\tinit\t\tBootstraps a new Divergence project.");
        static::$climate->out("\tstatus\t\tShows information on the current project.");
        static::$climate->out("\tbuild\t\tA suite of commands for automatically building project components.");
    }
}