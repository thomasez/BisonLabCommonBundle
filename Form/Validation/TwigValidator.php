<?php

namespace BisonLab\CommonBundle\Form\Validation;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * @Annotation
 */

class TwigValidator extends ConstraintValidator
{
    private $twig;

    public function __construct($twig)
    {
error_log("Ja");
        $this->twig = $twig;
    }
    
    public function validate($value, Constraint $constraint)
    {
error_log("Funk");
        $bin2hex_filter = new \Twig_SimpleFilter('bin2hex', 'bin2hex');
        $this->twig->addFilter($bin2hex_filter);
        $this->twig->addExtension(new \Twig_Extension_StringLoader());
        
        try {
            $nodeTree = $this->twig->parse($this->twig->tokenize($value));
        } catch (\Twig_Error $e) {
            $this->context->buildViolation($e->getMessage())
                ->addViolation();
        }
    }

    public function oldis($value, Constraint $constraint)
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
