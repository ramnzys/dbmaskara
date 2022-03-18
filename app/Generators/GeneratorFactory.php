<?php

namespace App\Generators;

use App\Generators\FakerGenerator;
use Exception;

/** @package App\Generators */
class GeneratorFactory
{

    /**
     * @param mixed $maskOptions
     * @return GeneratorInterface
     */
    public static function create($maskOptions)
    {
        switch ($maskOptions['generator']) {
            case 'faker':
                return new FakerGenerator($maskOptions);
                break;
            case 'static':
                return new StaticGenerator($maskOptions);
                break;
            default:
                throw new Exception("Generator nor supported", 1);
                break;
        }
    }
}
