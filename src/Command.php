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
use Divergence\CLI\Controllers\CommandLineHandler;
use Divergence\CLI\Controllers\Commands\Basics;
use Divergence\CLI\Controllers\Commands\Tester;
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
        //static::$climate->style->addCommand('orange', '38;5;208' /*'38;5;208'*/);
        //static::$climate->orange('test'); exit;

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