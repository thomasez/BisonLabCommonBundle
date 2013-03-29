<?php

namespace RedpillLinpro\CommonBundle\Extension;
  
/*
 * This takes a csv file and make a html table from it.
 * 
 */

class TwigExtensionCsv2Html extends \Twig_Extension
{
   
   public function getFilters()
   {
  
        return array(
            'csv2html' => new \Twig_Filter_Function('\RedpillLinpro\NosqlBundle\Extension\twig_csv2html', 
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
        return 'csv2html';
    }

}  

function c2v2html($filename) 
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

function twig_csv2html(\Twig_Environment $env, $value, $length = 80, $separator = "\n", $preserve = false)
{
return;
}

