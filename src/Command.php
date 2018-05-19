<?php
namespace Divergence\CLI;

use Divergence\CLI\Env;
use Divergence\CLI\Controllers\CommandLineHandler;
use Divergence\CLI\Controllers\Commands\Basics;
use Divergence\CLI\Controllers\Commands\Initialize;

use \League\CLImate\CLImate;

class Command extends CommandLineHandler
{
    public static $climate; // instance of \League\CLImate\CLImate;

    public static function getClimate()
    {
        return static::$climate;
    }

    public static function handle()
    {
        Env::getEnvironment();

        Env::$me = static::shiftArgs();

        static::$climate = new CLImate();

        switch($action = static::shiftArgs()) {
            case 'init':
                Initialize::init();
            break;

            case 'status':
                Basics::status();
            break;

            case 'build':
                // Divergence\Controllers\Builder::handle();
            break;

            case '-v':
            case '--version':
                Basics::version();
            break;

            case '--help':
            case '-h':
            case 'help':
            default:
                Basics::usage();
        }
    }
}