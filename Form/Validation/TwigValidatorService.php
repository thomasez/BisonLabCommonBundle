<?php

namespace BisonLab\CommonBundle\Form\Validation;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * @Annotation
 * The Twig Validator, service version.
 * Should handle all extensions and filters added to the twig service.
 */

class TwigValidatorService extends ConstraintValidator
{
    private $twig;

    public function __construct($twig)
    {
        $this->twig = $twig;
    }
    
    public function validate($value, Constraint $constraint)
    {
        try {
            $nodeTree = $this->twig->parse($this->twig->tokenize($value));
        } catch (\Twig_Error $e) {
            $this->context->buildViolation($e->getMessage())
                ->addViolation();
        }
    }
}
