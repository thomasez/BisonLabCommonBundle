<?php

namespace BisonLab\CommonBundle\Extension;

/*
 * This was (and stil is) a Pretty printer for the NoSqlBundle arrays / objects.
 * Aka I've nicked it from there.
 * 
 */

class TwigExtensionPrettyPrint extends \Twig_Extension
{

   public function getFilters()
   {

        return array(
            'prettyprint' => new \Twig_Filter_Function('\BisonLab\CommonBundle\Extension\twig_pretty_print_filter',
                array('needs_environment' => true)),

        );
    }

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'pretty_print';
    }

}

function pretty($data)
{
    if (empty($data)) { return ""; }

    echo "<table>\n";
    foreach($data as $key => $value) {

        echo "<tr>\n<th valign='top'>$key</th>\n<td>";
        if (is_array($value)) {
            pretty($value);
        } else {
            // I want to change \n to <br />. Not perfect but I need it.
            $value = preg_replace("/\n/", "<br />", $value);
            echo $value . "\n";
        }

        echo "</td>\n</tr>\n";

    }
    echo "</table>\n";

}

function twig_pretty_print_filter(\Twig_Environment $env, $value, $length = 80, $separator = "\n", $preserve = false)
{
    pretty($value);
    return;
}

