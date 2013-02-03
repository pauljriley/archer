<?php
namespace Icecave\Testing\FileSystem;

use ErrorException;
use Icecave\Testing\Support\Isolator;

class FileSystem
{
    /**
     * @param Isolator|null $isolator
     */
    public function __construct(Isolator $isolator = null)
    {
        $this->isolator = Isolator::get($isolator);
    }

    /**
     * @param string $path
     *
     * @return boolean
     */
    public function exists($path)
    {
        try {
            $exists = $this->isolator->file_exists($path);
        } catch (ErrorException $e) {
            throw new Exception\ReadException($path, $e);
        }

        return $exists;
    }

    /**
     * @param string $path
     *
     * @return boolean
     */
    public function fileExists($path)
    {
        try {
            $exists = $this->isolator->is_file($path);
        } catch (ErrorException $e) {
            throw new Exception\ReadException($path, $e);
        }

        return $exists;
    }

    /**
     * @param string $path
     *
     * @return boolean
     */
    public function directoryExists($path)
    {
        try {
            $exists = $this->isolator->is_dir($path);
        } catch (ErrorException $e) {
            throw new Exception\ReadException($path, $e);
        }

        return $exists;
    }

    /**
     * @param string $path
     *
     * @return string
     */
    public function read($path)
    {
        try {
            $content = $this->isolator->file_get_contents($path);
        } catch (ErrorException $e) {
            throw new Exception\ReadException($path, $e);
        }

        return $content;
    }

    /**
     * @param string $path
     *
     * @return array<string>
     */
    public function listPaths($path)
    {
        try {
            $rawItems = $this->isolator->scandir($path);
        } catch (ErrorException $e) {
            throw new Exception\ReadException($path, $e);
        }

        $items = array();
        foreach ($rawItems as $item) {
            if ('.' !== $item && '..' !== $item) {
                $items[] = $item;
            }
        }

        return $items;
    }

    /**
     * @param string $path
     * @param string $content
     */
    public function write($path, $content)
    {
        $this->ensureParentDirectory($path);

        try {
            $this->isolator->file_put_contents($path, $content);
        } catch (ErrorException $e) {
            throw new Exception\WriteException($path, $e);
        }
    }

    /**
     * @param string $source
     * @param string $destination
     */
    public function copy($source, $destination)
    {
        $this->ensureParentDirectory($destination);

        try {
            $this->isolator->copy($source, $destination);
        } catch (ErrorException $e) {
            throw new Exception\WriteException($destination, $e);
        }
    }

    /**
     * @param string $source
     * @param string $destination
     */
    public function move($source, $destination)
    {
        $this->ensureParentDirectory($destination);

        try {
            $this->isolator->rename($source, $destination);
        } catch (ErrorException $e) {
            throw new Exception\WriteException($destination, $e);
        }
    }

    /**
     * @param string $path
     */
    public function createDirectory($path)
    {
        try {
            $this->isolator->mkdir($path, 0777, true);
        } catch (ErrorException $e) {
            throw new Exception\WriteException($path, $e);
        }
    }

    /**
     * @param string $path
     */
    public function delete($path)
    {
        if ($this->directoryExists($path)) {
            foreach ($this->listPaths($path) as $subPath) {
                $this->delete(sprintf('%s/%s', $path, $subPath));
            }

            try {
                $this->isolator->rmdir($path);
            } catch (ErrorException $e) {
                throw new Exception\WriteException($path, $e);
            }
        } else {
            try {
                $this->isolator->unlink($path);
            } catch (ErrorException $e) {
                throw new Exception\WriteException($path, $e);
            }
        }
    }

    /**
     * @param string $path
     */
    protected function ensureParentDirectory($path)
    {
        try {
            $parentPath = $this->isolator->dirname($path);
        } catch (ErrorException $e) {
            throw new Exception\ReadException($path, $e);
        }

        if (!$this->directoryExists($parentPath)) {
            $this->createDirectory($parentPath);
        }
    }

    private $isolator;
}
