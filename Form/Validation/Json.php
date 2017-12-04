<?php

namespace BisonLab\CommonBundle\Form\Validation;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */

class Json extends Constraint
{
    public function validatedBy()
    {
        return get_class($this).'Validator';
    }
}
