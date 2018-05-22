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

use PDO;
use Divergence\CLI\Env;
use Divergence\CLI\Command;
use Divergence\CLI\ConfigWriter;

class Database
{
    public static function wizard($config)
    {
        $climate = Command::getClimate();
        do {
            $config = Database::configBuilder($config);
            $climate->info('For your reference the config is:');

            /* do not remove */
            dump($config);
            
            $retest = true;
            do { // retest config until success or abort
                $climate->inline('Testing config.......... ');
                $valid = static::connectionTester($config);
                if ($valid) {
                    $climate->green('Success.');
                    return $config;
                } else {
                    $climate->red('Failed.');
                    $input = $climate->confirm('Test again?');
                    $input->defaultTo('y');
                    $retest = $input->confirmed();
                }
            } while ($retest);
            $input = $climate->confirm('Continue with untested config?');
            $input->defaultTo('n');
            if ($input->confirmed()) {
                $valid = true; // human override
            }
        } while (!$valid);

        return $config;
    }

    /*
     *  Asks the user to define a database config with some simple helpers
     */
    public static function configBuilder($defaults)
    {
        $climate = Command::getClimate();
        
        $new = [];

        // hostname or socket
        $input = $climate->input(sprintf('Hostname (You can also provide a socket) <yellow>[<bold>%s</bold>]</yellow>', $defaults['host']));
        $input->defaultTo($defaults['host']);
        $new['host'] = $input->prompt();
        if (substr($new['host'], -5) === '.sock') { // if ends with .sock treat as socket
            $new['socket'] = $new['host'];
            unset($new['host']);
        }

        // database name
        $input = $climate->input(sprintf('Database name <yellow>[<bold>%s</bold>]</yellow>', $defaults['database']));
        $input->defaultTo($defaults['database']);
        $new['database'] = $input->prompt();

        // database username
        $input = $climate->input(sprintf('Database username <yellow>[<bold>%s</bold>]</yellow>', $defaults['username']));
        $input->defaultTo($defaults['username']);
        $new['username'] = $input->prompt();

        // database password
        $input = $climate->input('Database password: ');
        $new['password'] = $input->prompt();
        if (!$new['password']) {
            $input = $climate->confirm('Generate one?');
            $input->defaultTo('y');
            if ($input->confirmed()) {
                $new['password'] = static::createPassword();
            }
        }
        return $new;
    }

    /*
     * Tries to connect to a database from a config
     */
    public static function connectionTester($config)
    {
        if ($config['socket']) {
            $DSN = 'mysql:unix_socket=' . $config['socket'] . ';dbname=' . $config['database'];
        } else {
            $DSN = 'mysql:host=' . $config['host'] . ';port=' . $config['port'] .';dbname=' . $config['database'];
        }
        
        try {
            new PDO($DSN, $config['username'], $config['password']);
        } catch (\PDOException $e) {
            return false;
        }

        return true;
    }

    /*
     *  Uses ascii table chars decimal 33 (!) -> 126 (~)
     *  covers basic symbols and letters
     */
    public static function createPassword($length=20)
    {
        $password = '';
        while (strlen($password)<$length) {
            $password .= chr(mt_rand(33, 126));
        }
        return $password;
    }
}
