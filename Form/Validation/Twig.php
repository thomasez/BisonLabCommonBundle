<?php

namespace BisonLab\CommonBundle\Form\Validation;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */

class Twig extends Constraint
{
    public function validatedBy()
    {
        return get_class($this).'Validator';
    }
}
