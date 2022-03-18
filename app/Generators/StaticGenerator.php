<?php

namespace App\Generators;

/** @package App\Generators */
class StaticGenerator  implements GeneratorInterface
{
    private $value;

    public function __construct($maskOptions)
    {
        $this->value = $maskOptions['value'] ?? '';
    }

    /** @return string|null  */
    public function generateValue()
    {
        return $this->value;
    }
}
