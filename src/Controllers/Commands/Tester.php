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

use Divergence\CLI\Env;
use Divergence\CLI\Command;
use Divergence\CLI\Controllers\CommandLineHandler;

class Tester extends CommandLineHandler
{
    public static function handle()
    {
        switch ($action = static::shiftArgs()) {
            case 'dbconfig':
                static::dbconfig();
            break;

            default:
                Basics::usage();
        }
    }

    public static function error($error)
    {
        Command::$climate->error($error);
    }

    public static function dbconfig()
    {
        $climate = Command::$climate;

        try {
            $configs = Env::getConfig(getcwd(),'db');
        } catch(\Exception $e) {

            $climate->shout('No database config found! Are you sure this is a project folder?');
            return;
        }
        $labels = array_keys($configs);

        if (!$label = static::shiftArgs()) {
            $input = $climate->radio('Choose a config to test:', $labels);
            $response = $input->prompt();
            
            if (in_array($response, $labels)) {
                static::testDatabaseConfig($configs[$response]);
            }
        } else {
            if (in_array($label, $labels)) {
                static::testDatabaseConfig($configs[$label]);
            } else {
                $climate->yellow('No database config found with that label.');
            }
        }
    }

    public static function testDatabaseConfig($config)
    {
        $climate = Command::$climate;
        $climate->inline('Testing config.......... ');
        if (Database::connectionTester($config)) {
            $climate->green('Success.');
        } else {
            $climate->red('Failed.');
        }
    }
}
