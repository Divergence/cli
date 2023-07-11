<?php
/*
 * This file is part of the Divergence package.
 *
 * (c) Henry Paradiz <henry.paradiz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Divergence\CLI;

use Divergence\CLI\Env;
use \League\CLImate\CLImate;
use Divergence\CLI\Controllers\Commands\Basics;
use Divergence\CLI\Controllers\Commands\Config;
use Divergence\CLI\Controllers\Commands\Tester;
use Divergence\CLI\Controllers\CommandLineHandler;

use Divergence\CLI\Controllers\Commands\Initialize;

class Command extends CommandLineHandler
{
    public static $climate; // instance of \League\CLImate\CLImate;

    public static function getClimate()
    {
        return static::$climate;
    }

    public static function handle()
    {
        static::$climate = new CLImate();
        try {
            Env::getEnvironment();
        } catch(\Exception $e) {
            static::$climate->error(PHP_EOL.$e->getMessage().PHP_EOL);
            return Basics::usage();
        }
        

        Env::$me = static::shiftArgs();

        switch ($action = static::shiftArgs()) {
            case 'init':
                Initialize::init();
            break;

            case 'status':
                Basics::status();
            break;

            case 'config':
                Config::handle();

                // no break
            case 'build':
                // Divergence\Controllers\Builder::handle();
            break;

            case 'test':
                Tester::handle();
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
