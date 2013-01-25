<?php
namespace Icecave\Woodhouse\Coverage\Readers;

use Icecave\Woodhouse\TypeCheck\TypeCheck;
use Icecave\Woodhouse\Coverage\CoverageReaderInterface;

class CommandLineReader implements CoverageReaderInterface
{
    /**
     * @param numeric $percentage
     */
    public function __construct($percentage)
    {
        $this->typeCheck = TypeCheck::get(__CLASS__, func_get_args());

        $this->percentage = floatval($percentage);
    }

    /**
     * @return float
     */
    public function readPercentage()
    {
        $this->typeCheck->readPercentage(func_get_args());

        return $this->percentage;
    }

    private $typeCheck;
    private $percentage;
}
