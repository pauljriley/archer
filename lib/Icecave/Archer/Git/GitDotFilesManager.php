<?php
namespace Icecave\Archer\Git;

use Icecave\Archer\FileSystem\FileSystem;

class GitDotFilesManager
{
    /**
     * @param FileSystem|null $fileSystem
     * @param Isolator|null   $isolator
     */
    public function __construct(FileSystem $fileSystem = null)
    {
        if (null === $fileSystem) {
            $fileSystem = new FileSystem;
        }

        $this->fileSystem = $fileSystem;
    }

    /**
     * @return FileSystem
     */
    public function fileSystem()
    {
        return $this->fileSystem;
    }

    /**
     * @param string $archerPackageRoot
     * @param string $packageRoot
     *
     * @return array<string, boolean>
     */
    public function updateDotFiles($archerPackageRoot, $packageRoot)
    {
        $files = array(
            '.gitignore'     => false,
            '.gitattributes' => false
        );

        foreach ($files as $filename => $updated) {
            $targetPath = sprintf('%s/%s', $packageRoot, $filename);
            $sourcePath = sprintf('%s/res/git/%s', $archerPackageRoot, substr($filename, 1));

            if ($this->fileSystem->fileExists($targetPath)) {
                $existing = $this->fileSystem->read($targetPath);
            } else {
                $existing = '';
            }

            $content = $this->replaceContent(
                $existing,
                $this->fileSystem->read($sourcePath)
            );

            if ($existing !== $content) {
                $files[$filename] = true;
                $this->fileSystem->write($targetPath, $content);
            }
        }

        return $files;
    }

    protected function replaceContent($existing, $source)
    {
        $start = '# archer start';
        $end   = '# archer end';

        $existing = trim($existing);
        $source   = trim($source);
        $enclosed = $start . PHP_EOL
                . $source . PHP_EOL
                . $end . PHP_EOL;

        // The existing content is exactly the same as the resource
        // content so just wrap it in the start/end tags ...
        if ($existing === $source) {
            return $enclosed;
        }

        $sourceBlock = false;
        $replaced = false;
        $output = '';

        foreach (explode(PHP_EOL, $existing) as $line) {
            $line = trim($line);

            // Start of the tagged source block within the existing content ...
            if ($line === $start) {
                $sourceBlock = true;
                $replaced = true;
                $output .= $enclosed;

            // End of the tagged source block ...
            } elseif ($line === $end) {
                $sourceBlock = false;

            // An un-managed custom line, include unchanged ...
            } elseif (!$sourceBlock) {
                $output .= $line . PHP_EOL;
            }
        }

        // There was no tagged source block in the existing content
        // so it is just appended to the existing content ...
        if (!$replaced) {
            $output .= PHP_EOL . $enclosed;
        }

        return trim($output) . PHP_EOL;
    }

    private $fileSystem;
}
