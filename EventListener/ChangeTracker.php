<?php

namespace BisonLab\CommonBundle\EventListener;

use Doctrine\ORM\Event\PreUpdateEventArgs;

/*
 * Does as little as possible.
 */

class ChangeTracker 
{
    public function preUpdate(PreUpdateEventArgs $eventArgs)
    {
        if ($eventArgs->hasChangedField('attributes_json')) {
            if ($json = $eventArgs->getNewValue('attributes_json')) {
                $la = $this->array_change_key_case_recursive(json_decode($json, true), MB_CASE_LOWER);
                $json = json_encode($la);
                $eventArgs->setNewValue('attributes_json', $json);
            }
        }
    }
}
