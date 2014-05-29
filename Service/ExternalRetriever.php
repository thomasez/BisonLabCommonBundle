<?php

namespace RedpillLinpro\CommonBundle\Service;


/*
 * Absurdly simple. This is the single point for retrieving external data from 
 * a context. Aka, the main point with the context system.
 *
 * You will need a retriever service per external system. (Which of course can
 * point at the same retriever file.)
 * 
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

        $rname = strtolower($context->getSystem() . "_retriever");
error_log("Gonna retrieve from " . $rname);
        $retriever = $this->container->get($rname);
        return $retriever->getExternalDataFromContext($context);

    }

}
