<?php

namespace BisonLab\CommonBundle\EventListener;

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

        $this->_insertConfig($args);

    }

    public function prePersist(LifecycleEventArgs $args)
    {

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
            // Gotta be able to handle the case of no config at all..
            if (isset($context_conf[$bundle][$object]) 
                    && $context_conf[$bundle][$object][$entity->getSystem()]) {
                foreach ($context_conf[$bundle][$object][$entity->getSystem()] as $c)
                {
                    if ($c['object_name'] == $object_name) {
                        $conf = $c;
                        break;
                    }
                }
                // You may end up with an error point at this place.
                // The reason for this is that you haven't configured
                // contexts.yml properly. You might miss either a system or
                // object_name.
                $entity->setConfig($conf);
                return $entity;
            }
        }
        
        // Had nothing to do.
        return false;

    }
}
