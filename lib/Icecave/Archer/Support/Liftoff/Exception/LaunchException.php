<?php
namespace Icecave\Archer\Support\Liftoff\Exception;

use Exception;
use RuntimeException;

/**
 * Launch command failed, or is unavailable.
 *
 * This class is a partial implementation of eloquent/liftoff, provided here to prevent
 * circular dependencies and namespace clashes.
 *
 * Please see https://github.com/eloquent/liftoff for a usable implementation.
 */
final class LaunchException extends RuntimeException
{
    /**
     * Create a new LaunchException instance.
     *
     * @param string         $target   The target that Liftoff attempted to launch.
     * @param Exception|null $previous The previous exception, if available.
     */
    public function __construct($target, Exception $previous = null)
    {
        $this->target = $target;

        parent::__construct(
            sprintf('Unable to launch %s.', var_export($target, true)),
            0,
            $previous
        );
    }

    /**
     * Get the target that Liftoff attempted to launch.
     *
     * @return string The target that Liftoff attempted to launch.
     */
    public function target()
    {
        return $this->target;
    }

    private $target;
}
