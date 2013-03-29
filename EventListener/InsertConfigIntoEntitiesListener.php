<?php

namespace RedpillLinpro\CommonBundle\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;

/*
 * This is so wrong I am afraid I'll go mad.
 */

class InsertConfigIntoEntitiesListener 
{

    private $container;

/*
    public function __construct($container)
    {
         $this->container = $container;
    }
*/

    public function setContainer($container)
    {
         $this->container = $container;
    }

    public function postLoad(LifecycleEventArgs $args)
    {
         $entity = $args->getEntity();

//If you feel like being show hos much is actually loaded, decomment this one..
//error_log("Hva har vi:" . get_class($entity));

         // perhaps you only want to act on some "Product" entity
        if (preg_match("/Context/", get_class($entity))) {
            $context_conf = $this->container->getParameter('app.contexts');
            list($bundle, $object) = explode(":", $entity->getContextForEntity());
            // do something with the Product
            $object_name = $entity->getObjectName();
            foreach ($context_conf[$bundle][$object][$entity->getSystem()] as $c)
            {
                if ($c['object_name'] == $object_name) {
                    $conf = $c;
                    break;
                }
            }
            $entity->setConfig($conf);
        }
    }
}
