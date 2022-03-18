<?php

namespace App\Generators;

use Exception;
use Faker\Factory;

/** @package App\Generators */
class FakerGenerator  implements GeneratorInterface
{
    private $formatter;
    private $params;
    private $transform;
    private $locale;

    public function __construct($maskOptions)
    {
        $this->formatter = $maskOptions['formatter'] ?? '';
        $this->params = $maskOptions['params'] ?? [];
        $this->transform = $maskOptions['transform'] ?? [];
        $this->locale = $maskOptions['locale'] ?? '';
    }

    /** @return string|null  */
    public function generateValue()
    {
        $valueGenerator = Factory::create($this->locale);
        $value = $valueGenerator->__call($this->formatter, $this->params);
        return $value;
    }
}
