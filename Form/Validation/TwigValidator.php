<?php

namespace BisonLab\CommonBundle\Form\Validation;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * @Annotation
 */

class TwigValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        $loader = new \Twig_Loader_Array();
        $twig = new \Twig_Environment($loader, array(
            // 'debug' => true,
        ));
        $bin2hex_filter = new \Twig_SimpleFilter('bin2hex', 'bin2hex');
        $twig->addFilter($bin2hex_filter);
        $twig->addExtension(new \Twig_Extension_StringLoader());
        
        try {
            $nodeTree = $twig->parse($twig->tokenize($value));
        } catch (\Twig_Error $e) {
            $this->context->buildViolation($e->getMessage())
                ->addViolation();
        }
    }
}
