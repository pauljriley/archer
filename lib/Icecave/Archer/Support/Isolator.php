<?php
namespace Icecave\Archer\Support;

/**
 * This class is a partial implementation of icecave/isolator, provided here to prevent
 * circular dependencies and namespace clashes.
 *
 * Please see https://github.com/IcecaveStudios/isolator for a usable implementation.
 */
class Isolator
{
    public static function get(Isolator $instance = NULL)
    {
        if ($instance) {
            return $instance;
        } elseif (self::$instance) {
            return self::$instance;
        } else {
            return self::$instance = new self;
        }
    }

    public static function resetIsolator()
    {
        self::$instance = null;
    }

    public function __call($name, array $arguments)
    {
        switch ($name) {
            case 'exit':
            case 'die':
                // @codeCoverageIgnoreStart
                exit(current($arguments));
                // @codeCoverageIgnoreEnd
            case 'echo':
                echo current($arguments);

                return;
            case 'eval':
                return eval(current($arguments));
            case 'include':
                return include current($arguments);
            case 'include_once':
                return include_once current($arguments);
            case 'require':
                return require current($arguments);
            case 'require_once':
                return require_once current($arguments);
            default:

        }

        return call_user_func_array($name, $arguments);
    }

    // @codeCoverageIgnoreStart

    public function openssl_public_encrypt($data, &$crypted, $key, $padding = null)
    {
        if (null === $padding) {
            return openssl_public_encrypt($data, $crypted, $key);
        }

        return openssl_public_encrypt($data, $crypted, $key, $padding);
    }

    public function exec($command, &$output = null, &$exitCode = null)
    {
        return exec($command, $output, $exitCode);
    }

    public function passthru($command, &$exitCode = null)
    {
        return passthru($command, $exitCode);
    }

    // @codeCoverageIgnoreEnd

    private static $instance;
}
