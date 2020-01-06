<?php

namespace BisonLab\CommonBundle\Form\Validation;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Twig\Extension\StringLoaderExtension;
use Twig\Source as TwigSource;
use Twig\Loader\ArrayLoader;
use Twig\TwigFilter;
use Twig\Error\Error as TwigError;
use Twig\Environment as TwigEnvironment;

/**
 * @Annotation
 * Quite simple thingie, does not handle extensions/filters very well.
 */

class TwigValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        $loader = new ArrayLoader();
        $twig = new TwigEnvironment($loader, array(
            // 'debug' => true,
        ));
        // I have been using these a lot, alas I'll keep it here or my
        // validations bork.
        $bin2hex_filter = new TwigFilter('bin2hex', 'bin2hex');
        $twig->addFilter($bin2hex_filter);
        $twig->addExtension(new StringLoaderExtension());
        
        try {
            $tokens = $twig->tokenize(new TwigSource($value, 'validation'));
            $nodeTree  = $twig->parse($tokens);
        } catch (TwigError $e) {
            $this->context->buildViolation($e->getMessage())
                ->addViolation();
        }
    }
}
