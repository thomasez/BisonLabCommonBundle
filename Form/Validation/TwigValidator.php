<?php

namespace BisonLab\CommonBundle\Form\Validation;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * @Annotation
 * Quite simple thingie, does not handle extensions/filters very well.
 */

class TwigValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        $loader = new \Twig_Loader_Array();
        $twig = new \Twig_Environment($loader, array(
            // 'debug' => true,
        ));
        // I have been using these a lot, alas I'll keep it here or my
        // validations bork.
        $bin2hex_filter = new \Twig_SimpleFilter('bin2hex', 'bin2hex');
        $twig->addFilter($bin2hex_filter);
        $twig->addExtension(new \Twig_Extension_StringLoader());
        
        try {
            $tokens = $twig->tokenize(new \Twig_Source($value, 'validation'));
            $nodeTree  = $twig->parse($tokens);
        } catch (\Twig_Error $e) {
            $this->context->buildViolation($e->getMessage())
                ->addViolation();
        }
    }
}
