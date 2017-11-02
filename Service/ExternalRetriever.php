<?php

namespace BisonLab\CommonBundle\Service;

/*
 * Absurdly simple. This is the single point for retrieving external data from 
 * a context. Aka, the main point with the context system.
 *
 * You will need a retriever service per external system. (Which of course can
 * point at the same retriever file.)
 * 
 * I guess it should implement an interface, but with only one single 
 * function? I'm lazy.
 *
 * There is also another annoying point here. And that is when there are more
 * than one object related to the context.
 *
 * I'll chicken out and let the two ends of this decide how they like it.
 * (They should know what they are dealing with and therefor know how to handle
 * one or more returned objects or arrays.)
 *
 * The retriever end also has to remember security. It should ponder a bit
 * about who is asking.
 *
 * And I should ponder about how they can find out.
 */

class ExternalRetriever
{
    private $container;

    public function __construct($container)
    {
        $this->container         = $container;
    }

    public function getExternalDataFromContext($context) 
    {
dump($context);
        $rname = strtolower($context->getSystem() . "_retriever");
        $retriever = $this->container->get($rname);
        if (is_object($retriever)
            && method_exists($retriever, 'getExternalDataFromContext'))
                return $retriever->getExternalDataFromContext($context);
        else
            return null;
    }
}
