<?php
namespace Icecave\Woodhouse\Coverage\Readers;

use Icecave\Isolator\Isolator;
use Icecave\Woodhouse\Coverage\CoverageReaderInterface;
use Icecave\Woodhouse\TypeCheck\TypeCheck;
use RuntimeException;

class PHPUnitTextReader implements CoverageReaderInterface
{
    const PATTERN = '/^\s+Lines:\s+(\d{1,3}\.\d\d)/m';

    /**
     * @param string        $reportPath
     * @param Isolator|null $isolator
     */
    public function __construct($reportPath, Isolator $isolator = null)
    {
        $this->typeCheck = TypeCheck::get(__CLASS__, func_get_args());

        $this->reportPath = $reportPath;
        $this->isolator = Isolator::get($isolator);
    }

    /**
     * @return float
     */
    public function readPercentage()
    {
        $this->typeCheck->readPercentage(func_get_args());

        $content = $this->isolator->file_get_contents($this->reportPath);

        $matches = array();
        if (preg_match(self::PATTERN, $content, $matches)) {
            return floatval($matches[1]);
        }

        throw new RuntimeException('Unable to parse PHPUnit coverage report.');
    }

    private $typeCheck;
    private $reportPath;
    private $isolator;
}
