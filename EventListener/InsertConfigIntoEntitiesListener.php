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

//If you feel like being shown how much is actually loaded, decomment this one..
//error_log("Hva har vi:" . get_class($entity));
        $this->_insertConfig($args);

    }

    public function prePersist(LifecycleEventArgs $args)
    {

//If you feel like being shown how much is actually loaded, decomment this one..
//error_log("Hva har vi:" . get_class($entity));
        $entity = $this->_insertConfig($args);

        if ($entity && !$entity->getUrl() 
                && method_exists($entity, 'resetUrl')) {
            $entity->resetUrl();
        }

    }

    private function _insertConfig($args) {
        $entity = $args->getEntity();
        if (preg_match("/Context/", get_class($entity))) {
            $context_conf = $this->container->getParameter('app.contexts');
            list($bundle, $object) = explode(":", $entity->getOwnerEntity());
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
            return $entity;
        }
        
        // Had nothing to do.
        return false;

    }
}
