<?php
namespace Icecave\Woodhouse\Coverage;

use Icecave\Isolator\Isolator;
use Icecave\Woodhouse\Coverage\Readers\CommandLineReader;
use Icecave\Woodhouse\Coverage\Readers\PHPUnitTextReader;
use Icecave\Woodhouse\TypeCheck\TypeCheck;
use InvalidArgumentException;

class CoverageReaderFactory
{
    /**
     * @param Isolator|null $isolator
     */
    public function __construct(Isolator $isolator = null)
    {
        $this->typeCheck = TypeCheck::get(__CLASS__, func_get_args());

        $this->isolator = Isolator::get($isolator);
    }

    /**
     * @return array<string>
     */
    public function supportedTypes()
    {
        $this->typeCheck->supportedTypes(func_get_args());

        return array(
            'phpunit',
            'percentage'
        );
    }

    /**
     * @param string $type
     * @param string $argument
     *
     * @return CoverageReaderInterface
     */
    public function create($type, $argument)
    {
        $this->typeCheck->create(func_get_args());

        switch ($type) {
            case 'phpunit':
                return new PHPUnitTextReader($argument, $this->isolator);
            case 'percentage':
                return new CommandLineReader($argument);
        }

        throw new InvalidArgumentException('Unknown coverage type: "' . $type . '".');
    }

    private $typeCheck;
    private $isolator;
}
