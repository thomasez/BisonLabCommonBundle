<?php

namespace BisonLab\CommonBundle\Extension;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Twig\Environment as TwigEnvironment;
  
/*
 * This takes a csv file and make a html table from it.
 * 
 */

class TwigExtensionCsv2Html extends AbstractExtension
{
   public function getFilters(): array
   {
        return [ new TwigFunction('csv2html',
                    [$this, 'twig_csv2html'],
                    ['needs_environment' => true])
        ];
    }
  
    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName(): string
    {
        return 'csv2html';
    }

    public function c2v2html($filename) 
    {
        if (empty($value)) { return ""; }

        echo "<table>\n";
        foreach($value as $key => $value) {
            
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

    public function twig_csv2html(TwigEnvironment $env, $value, $length = 80, $separator = "\n", $preserve = false)
    {
        return;
    }

}  
