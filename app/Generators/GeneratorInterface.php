<?php

namespace App\Generators;

interface GeneratorInterface
{
    /**
     * Generates new masked value
     * @return string|null
     */
    public function generateValue();
}
