<?php

namespace BisonLab\CommonBundle\EventListener;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;

/*
 * Does as little as possible.
 */

class ChangeTracker 
{

    public function onFlush(OnFlushEventArgs $eventArgs)
    {
        $em = $eventArgs->getEntityManager();
        $uow = $em->getUnitOfWork();
        foreach ($uow->getScheduledEntityInsertions() as $entity) {
            if (in_array("BisonLab\CommonBundle\Entity\ContextBaseTrait",
                    class_uses($entity)))
                if ($entity->isUnique())
                    $this->_checkUnique($entity, $em);
        }
        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            if (in_array("BisonLab\CommonBundle\Entity\ContextBaseTrait",
                    class_uses($entity)))
                if ($entity->isUnique())
                    $this->_checkUnique($entity, $em);
        }
    }

    public function preUpdate(PreUpdateEventArgs $eventArgs)
    {
        if ($eventArgs->hasChangedField('attributes_json')) {
            if ($json = $eventArgs->getNewValue('attributes_json')) {
                $la = $this->_array_change_key_case_recursive(json_decode($json, true), MB_CASE_LOWER);
                $json = json_encode($la);
                $eventArgs->setNewValue('attributes_json', $json);
            }
        }
    }

    private function _array_change_key_case_recursive($arr, $case = MB_CASE_LOWER)
    {
        $ret = array();
        foreach ($arr as $k => $v) {
            if(is_array($v))
                $v = $this->_array_change_key_case_recursive($v, $case);
            $ret[mb_convert_case($k, $case, "UTF-8")] = $v;
        }
        return $ret;
    }

    private function _checkUnique($context, $em)
    {
        if ($exists = $em->getRepository(get_class($context))->findOneBy(array(
                'system' => $context->getSystem(),
                'object_name' => $context->getObjectName(),
                'external_id' => $context->getExternalId(),
            ))) {
            // My, myself or not I?
            if ($exists !== $context)
                throw new ConstraintDefinitionException("Context " . $context->getLabel() . " with external id " . $context->getExternalId() . " is already in use.");
        }
    }
}
