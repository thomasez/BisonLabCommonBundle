<?php

namespace BisonLab\CommonBundle\Extension;

/*
 * This was (and stil is) a Pretty printer for the NoSqlBundle arrays / objects.
 * It works on all arrays basically.
 * Aka I've nicked it from there.
 */

class TwigExtensionPrettyPrint extends \Twig_Extension
{
    private $attributes;

    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('prettyprint',  array($this, 'twig_pretty_print_filter'), array('needs_environment' => true)),
        );
    }

    private function composeTag($tag, $default = array())
    {
        $res = "<" . $tag;
        $attrs = array();

        if (isset($this->attributes[$tag])) {
            $attrs = $this->attributes[$tag];
        } else {
            $attrs = $default;
        }
        foreach ($attrs as $k => $v) {
            $res .= ' ' . $k . '="' . $v . '"';
        } 
        return $res . ">\n";
    }

    function pretty($data)
    {
        if (empty($data)) { return ""; }
        echo $this->composeTag('table');
        $tr = $this->composeTag('tr');
        $th = $this->composeTag('th', array('valign' => 'top'));
        $td = $this->composeTag('td');

        foreach($data as $key => $value) {
            echo $tr;
            echo $th . $key . "</th>\n";
            echo $td;
            if (is_array($value)) {
                $this->pretty($value);
            } else {
                // I want to change \n to <br />. Not perfect but I need it.
                $value = preg_replace("/\n/", "<br />", $value);
                echo $value . "\n";
            }
            echo "</td>\n</tr>\n";
        }
        echo "</table>\n";
    }

    function twig_pretty_print_filter(\Twig_Environment $env, $value, $attributes = array())
    {
        $this->attributes = $attributes;
        return $this->pretty($value);
    }
}
