<?php

namespace BisonLab\CommonBundle\Form\Validation;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * @Annotation
 */

class JsonValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        if (empty($value)) { return true; }

        if (!is_array(json_decode($value, true))) {
            $this->context->buildViolation("Not proper Json")
                ->addViolation();
        }
        return true;
    }
}
