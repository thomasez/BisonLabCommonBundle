<?php

namespace BisonLab\CommonBundle\Form\Validation;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Twig\Source as TwigSource;
use Twig\Error\Error as TwigError;

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
            $tokens = $this->twig->tokenize(new TwigSource($value, 'validation'));
            $nodeTree  = $this->twig->parse($tokens);
        } catch (TwigError $e) {
            $this->context->buildViolation($e->getMessage())
                ->addViolation();
        }
    }
}
