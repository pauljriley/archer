<?php
namespace Icecave\Woodhouse\Publisher;

use Icecave\Woodhouse\TypeCheck\TypeCheck;

abstract class AbstractPublisher implements PublisherInterface
{
    public function __construct()
    {
        $this->typeCheck = TypeCheck::get(__CLASS__, func_get_args());

        $this->contentPaths = array();
    }

    /**
     * Enqueue content to be published.
     *
     * @param string $sourcePath
     * @param string $targetPath
     */
    public function add($sourcePath, $targetPath)
    {
        $this->typeCheck->add(func_get_args());

        $this->contentPaths[$sourcePath] = ltrim($targetPath, '/');
    }

    /**
     * Remove enqueued content at $sourcePath.
     *
     * @param string $sourcePath
     */
    public function remove($sourcePath)
    {
        $this->typeCheck->remove(func_get_args());

        unset($this->contentPaths[$sourcePath]);
    }

    /**
     * Clear all enqueued content.
     */
    public function clear()
    {
        $this->typeCheck->clear(func_get_args());

        $this->contentPaths = array();
    }

    /**
     * @return array<string, string>
     */
    public function contentPaths()
    {
        $this->typeCheck->contentPaths(func_get_args());

        return $this->contentPaths;
    }

    private $typeCheck;
    private $contentPaths;
}
