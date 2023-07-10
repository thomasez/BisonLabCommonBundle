<?php

namespace BisonLab\CommonBundle\Form\Validation;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */

#[\Attribute(\Attribute::TARGET_PROPERTY)]
class Json extends Constraint
{
    public function validatedBy(): string
    {
        return get_class($this).'Validator';
    }
}
