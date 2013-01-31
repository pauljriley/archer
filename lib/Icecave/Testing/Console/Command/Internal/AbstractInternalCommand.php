<?php
namespace Icecave\Testing\Console\Command\Internal;

use Icecave\Testing\Support\Isolator;
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

    public function __construct(Isolator $isolator = null)
    {
        $this->isolator = Isolator::get($isolator);

        parent::__construct();
    }

    public function isEnabled()
    {
        if (null === self::$isEnabled) {
            $composerPath = sprintf(
                '%s/composer.json',
                $this->getApplication()->packageRoot()
            );

            if ($this->isolator->is_file($composerPath)) {
                $config = json_decode(
                    $this->isolator->file_get_contents($composerPath)
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
    protected $isolator;
}
