<?php
namespace Icecave\Testing\Support;

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

    public function openssl_public_encrypt($data, &$crypted, $key, $padding = OPENSSL_PKCS1_PADDING)
    {
        return openssl_public_encrypt($data, $crypted, $key, $padding);
    }

    private static $instance;
}
