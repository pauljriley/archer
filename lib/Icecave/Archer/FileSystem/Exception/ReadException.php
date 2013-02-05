<?php
namespace Icecave\Archer\FileSystem\Exception;

use Exception;
use RuntimeException;

final class ReadException extends RuntimeException
{
    /**
     * @param string         $path
     * @param Exception|null $previous
     */
    public function __construct($path, Exception $previous = null)
    {
        $this->path = $path;

        parent::__construct(
            sprintf("Unable to read from '%s'.", $path),
            0,
            $previous
        );
    }

    /**
     * @return string
     */
    public function path()
    {
        return $this->path;
    }

    private $path;
}
