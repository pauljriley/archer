<?php
namespace Icecave\Testing\Support;

use ErrorException;
use RuntimeException;

/**
 * This class is a partial implementation of eloquent/asplode, provided here to prevent
 * circular dependencies and namespace clashes.
 *
 * Please see https://github.com/eloquent/asplode for a usable implementation.
 */
class Asplode
{
    /**
     * @return Asplode
     */
    public static function instance()
    {
        return new static;
    }

    public function __construct(Isolator $isolator = null)
    {
        $this->isolator = Isolator::get($isolator);
    }

    public function install()
    {
        if ($this->installed) {
            throw new RuntimeException('Already installed.');
        }

        $this->isolator->set_error_handler(array($this, 'handleError'));
        $this->installed = true;
    }

    public function uninstall()
    {
        if (!$this->installed) {
            throw new RuntimeException('Not installed.');
        }

        $this->isolator->restore_error_handler();
        $this->installed = false;
    }

    public function handleError($severity, $message, $filename, $lineno)
    {
        throw new ErrorException($message, 0, $severity, $filename, $lineno);
    }

    protected $installed = false;
    protected $isolator;
}
