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
use Divergence\CLI\ConfigWriter;
use Divergence\CLI\Controllers\CommandLineHandler;

class Config extends CommandLineHandler
{
    public static function handle()
    {
        switch ($action = static::shiftArgs()) {
            case 'database':
                static::database();
            break;

            default:
                Basics::usage();
        }
    }

    public static function error($error)
    {
        Command::$climate->error($error);
    }

    public static function database()
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
            $input = $climate->radio('Choose a config to reconfigure:', $labels);
            $response = $input->prompt();
            dump($configs[$response]);
            if (in_array($response, $labels)) {
                static::wizardAndSave($response,$configs[$response]);
            }
        } else {
            if (in_array($label, $labels)) {
                static::wizardAndSave($label,$configs[$label]);
            } else {
                $climate->yellow('No database config found with that label.');
            }
        }
    }

    public static function wizardAndSave($label,$config)
    {
        $climate = Command::$climate;
        $config = Database::wizard($config);
        $input = $climate->confirm('Save this config?');
        $input->defaultTo('y');
        if ($input->confirmed()) {
            ConfigWriter::configWriter($label,$config);
        }
        return $config;
    }
}