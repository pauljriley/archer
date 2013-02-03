<?php
namespace Icecave\Testing\Console\Command\Internal;

use Icecave\Testing\FileSystem\FileSystem;
use Symfony\Component\Console\Command\Command;

abstract class AbstractInternalCommand extends Command
{
    /**
     * @param boolean|null $isEnabled
     */
    public static function setIsEnabled($isEnabled)
    {
        self::$isEnabled = $isEnabled;
    }

    /**
     * @param FileSystem|null $fileSystem
     */
    public function __construct(FileSystem $fileSystem = null)
    {
        if (null === $fileSystem) {
            $fileSystem = new FileSystem;
        }

        $this->fileSystem = $fileSystem;

        parent::__construct();
    }

    /**
     * @return FileSystem
     */
    public function fileSystem()
    {
        return $this->fileSystem;
    }

    public function isEnabled()
    {
        if (null === self::$isEnabled) {
            $composerPath = sprintf(
                '%s/composer.json',
                $this->getApplication()->packageRoot()
            );

            if ($this->fileSystem()->fileExists($composerPath)) {
                $config = json_decode(
                    $this->fileSystem()->read($composerPath)
                );

                self::setIsEnabled(
                    property_exists($config, 'name') &&
                    'icecave/testing' === $config->name
                );
            } else {
                self::setIsEnabled(false);
            }
        }

        return self::$isEnabled;
    }

    private static $isEnabled;
    private $fileSystem;
}
