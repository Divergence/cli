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

use Divergence\CLI\Command;

class ConfigWriter
{

    /*
     *  get source
     */
    public static function get_source($filename)
    {
        return file_get_contents($filename);
    }

    public static function set_source($filename, $source)
    {
        return file_put_contents($filename, $source);
    }

    /*
     *  get PHP tokens from filename
     */
    public static function get_tokens($filename)
    {
        return token_get_all(static::get_source($filename));
    }

    /*
     *  Rewrites db config based on passed in label and config
     */
    public static function configWriter($label, $config)
    {
        $climate = Command::getClimate();

        $climate->info(sprintf('Writing config: %s', $label));
        
        $existing = Env::getConfig(getcwd(), 'db');
        if ($existing[$label]) {
            $newConfig = static::editConfig($label, $config);
        } else {
            //  TODO: create new append
        }
        static::set_source(getcwd().'/config/db.php', $newConfig);
    }

    /*
     *  Tokenizes a config file replacing only the tokens we want to change and converts it back to PHP source
     *
     *  @returns string Divergence DB Config (PHP source code)
     */
    public static function editConfig($label, $config)
    {
        $climate = Command::getClimate();
        $climate->info('Config already exists so we need to rewrite.');
        $filename = getcwd().'/config/db.php';
        $tokens = static::get_tokens($filename);
        foreach ($config as $setting=>$value) {
            $tokens = ConfigWriter::tokenizedConfigValueRewriter($tokens, $label, $setting, $value);
        }
        return static::tokensToString($tokens);
    }

    /*
     *  Safely edits config tokens without changing human edits
     *
     *  @returns array  Edited tokens
     */
    public static function tokenizedConfigValueRewriter($tokens, $label, $setting, $value)
    {
        $climate = Command::getClimate();
        
        // find the key value pair we want in the existing config
        $found = static::findConfigKeyValuePairAsTokens($tokens, $label, $setting);
        if ($found) {
            $climate->info(sprintf('Rewriting tokens: %s[\'%s\'] = %s;', $label, $setting, $value)); // make for verbose mode
            // replace tokens and rebuild PHP file
            $index = $found['value']['index'];
            $token = $found['value']['token'];
            $value = addslashes($value);
            $token[1] = "'$value'";
            $tokens[$index] = $token;
        } else {
            // TODO: label exists but no key => value pair, create a new one at the end
        }
        
        return $tokens;
    }

    /*
     *  Converts tokens to PHP source code
     *
     *  @param  array $tokens Tokens in same format returned by token_get_all()
     *  @returns  string    PHP source code
     */
    public static function tokensToString($tokens)
    {
        $output;
        foreach ($tokens as $token) {
            if (is_array($token)) {
                $output .= $token[1];
            } else {
                $output .= $token;
            }
        }
        return $output;
    }

    /*
     *  Locates the key and value for the specific db config setting we want
     */
    public static function findConfigKeyValuePairAsTokens($tokens, $label, $setting)
    {
        //$climate = Command::getClimate();
        $depth = 0;
        $returnTokens = [];
        foreach ($tokens as $key=>$token) {
            if ($token == '[') {
                $depth++;
                continue;
            }
            // find config label
            if ($depth === 1 && $token[0]==323) { // T_CONSTANT_ENCAPSED_STRING
                $strValue = substr($token[1], 1, -1);
                if ($strValue == $label) {
                    $labelLine = $token[2]; // remember line # where the config we care about starts
                    $nextKey = true;
                }
            }

            // find key value pair
            if ($labelLine) {
                if ($depth === 2 && $token[0]==323) {
                    if ($nextKey) {
                        $nextKey = false;
                        // found key for setting
                        $strValue = substr($token[1], 1, -1);
                        if ($strValue === $setting) {
                            $returnTokens['key'] = ['token'=>$token,'index'=>$key];
                            //$climate->out(sprintf('Found key <yellow>%s</yellow> at line <green>%s</green>',$token[1],$token[2])); // make verbose mode
                            continue;
                        }
                    } else {
                        // next value once setting is found is the value we want
                        if ($returnTokens['key']) {
                            $strValue = substr($token[1], 1, -1);
                            //$climate->out(sprintf('Found value <yellow>%s</yellow> at line <green>%s</green>',$token[1],$token[2])); // make verbose mode
                            $returnTokens['value'] = ['token'=>$token,'index'=>$key];
                            return $returnTokens;
                        }
                        $nextKey = true;
                    }
                }
            }

            // keep track of closing brackets so we can ignore things
            if ($token == ']') {
                $depth--;

                if ($depth === 1) {
                    unset($labelLine); // clear line number once we detect the end
                }
            }
        }
    }
}
