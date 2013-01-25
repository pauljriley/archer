<?php
namespace Icecave\Woodhouse\Coverage;

use Icecave\Woodhouse\TypeCheck\TypeCheck;

class CoverageImageSelector
{
    /**
     * @param integer $increments
     */
    public function __construct($increments = 5)
    {
        $this->typeCheck = TypeCheck::get(__CLASS__, func_get_args());

        $this->increments = $increments;
    }

    /**
     * @param float $percentage The actual percentage coverage.
     *
     * @return integer The coverage percentage rounded do the nearest 5%.
     */
    public function roundPercentage($percentage)
    {
        $this->typeCheck->roundPercentage(func_get_args());

        return intval($percentage - $percentage % $this->increments);
    }

    /**
     * @param float $percentage The actual percentage coverage.
     *
     * @return string The filename of the image to use.
     */
    public function imageFilename($percentage)
    {
        $this->typeCheck->imageFilename(func_get_args());

        $percentage = $this->roundPercentage($percentage);

        return sprintf('test-coverage-%03d.png', $percentage);
    }

    private $typeCheck;
    private $increments;
}
