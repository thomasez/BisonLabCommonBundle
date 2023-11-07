<?php

namespace BisonLab\CommonBundle\Form\Validation;

use Symfony\Component\Validator\Constraint;

/**
 * Symfony now has it's own Json validator.
 * Use Assert\Json instead.
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
class Json extends Constraint
{
    public function validatedBy(): string
    {
        return get_class($this).'Validator';
    }
}
