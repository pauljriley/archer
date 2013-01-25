<?php
namespace Icecave\Woodhouse\Coverage;

interface CoverageReaderInterface
{
    /**
     * @return float
     */
    public function readPercentage();
}
