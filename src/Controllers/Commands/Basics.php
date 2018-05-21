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

use Divergence\CLI\Command;
use Divergence\CLI\Env;

class Basics
{
    public static function version()
    {
        $climate = Command::getClimate();
        $climate->out('Divergence Command Line Tool');
    }

    public static function status()
    {
        $climate = Command::getClimate();

        if(!Env::$hasComposer) {
            $climate->backgroundYellow()->black()->out("No composer.json detected.");
            return;
        }

        $climate->info(sprintf('Found %s',Env::$package['name']));
        
        if(!Env::$isRequired && !Env::$isRequireDev) {
            $climate->backgroundYellow()->black()->out('Did not find divergence/divergence in composer.json require');
            $climate->backgroundYellow()->black()->out('Run divergence init to bootstrap your project');
            return;
        }

        if(Env::$isRequired) {
            $climate->info('Found divergence/divergence in composer.json require');
        }

        if(Env::$isRequireDev) {
            $climate->info('Found divergence/divergence in composer.json require-dev');
        }
    }
    public static function usage()
    {
        $climate = Command::getClimate();
        static::version();
        $climate->out('');
        $climate->out(" divergence [command] [arguments]");
        $climate->out('');
        $climate->out('');
        $climate->bold("\tAvailable Arguments");
        $climate->out("\t--version, -v\t\tVersion information");
        $climate->out('');
        $climate->out("\thelp, --help, -h\tThis help information");
        $climate->out('');
        $climate->bold("\tAvailable Commands");
        $climate->out('');
        $climate->out("\tinit\t\tBootstraps a new Divergence project.");
        $climate->out("\tstatus\t\tShows information on the current project.");
        $climate->out("\tbuild\t\tA suite of commands for automatically building project components.");
        $climate->out('');
        $climate->out("\ttest [subcommand]\t\t");
        $climate->out("\t     dbconfig\tChecks if DB config works. Asks you to choose a label name or provide one as the next argument.");
    }
}