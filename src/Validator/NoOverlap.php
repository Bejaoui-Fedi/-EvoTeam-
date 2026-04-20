<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 * @Target({"CLASS", "ANNOTATION"})
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class NoOverlap extends Constraint
{
    public string $message = 'Un rendez-vous existe déjà à cette date et cette heure.';

    public function getTargets(): string|array
    {
        return self::CLASS_CONSTRAINT;
    }
}
