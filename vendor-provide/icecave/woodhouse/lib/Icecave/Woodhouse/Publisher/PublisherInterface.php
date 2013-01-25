<?php
namespace Icecave\Woodhouse\Publisher;

interface PublisherInterface
{
    /**
     * Enqueue content to be published.
     *
     * @param string $sourcePath
     * @param string $targetPath
     */
    public function add($sourcePath, $targetPath);

    /**
     * Remove enqueued content at $sourcePath.
     *
     * @param string $sourcePath
     */
    public function remove($sourcePath);

    /**
     * Clear all enqueued content.
     */
    public function clear();

    /**
     * Publish enqueued content.
     */
    public function publish();
}
