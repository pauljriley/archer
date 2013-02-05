<?php
namespace Icecave\Archer\FileSystem\Exception;

use Exception;
use RuntimeException;

final class WriteException extends RuntimeException
{
    /**
     * @param string         $path
     * @param Exception|null $previous
     */
    public function __construct($path, Exception $previous = null)
    {
        $this->path = $path;

        parent::__construct(
            sprintf("Unable to write to '%s'.", $path),
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
