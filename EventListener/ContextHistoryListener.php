<?php

namespace BisonLab\CommonBundle\EventListener;

use Doctrine\Common\EventArgs;
use BisonLab\CommonBundle\Entity\ContextLog;

/*
 * Does as little as possible.
 */

class ContextHistoryListener 
{
    private $uow;
    private $em;
    private $token_storage;

    public function __construct($token_storage)
    {
        $this->token_storage = $token_storage;
    }

    public function onFlush(EventArgs $eventArgs)
    {
        $this->em = $eventArgs->getEntityManager();
        $this->uow = $this->em->getUnitOfWork();

        foreach ($this->uow->getScheduledEntityInsertions() as $entity) {
            if (in_array("BisonLab\CommonBundle\Entity\ContextBaseTrait", class_uses($entity)))
                $this->logContext($entity, 'create');
        }
        foreach ($this->uow->getScheduledEntityUpdates() as $entity) {
            if (in_array("BisonLab\CommonBundle\Entity\ContextBaseTrait", class_uses($entity)))
                $this->logContext($entity, 'update');
        }
        foreach ($this->uow->getScheduledEntityDeletions() as $entity) {
            if (in_array("BisonLab\CommonBundle\Entity\ContextBaseTrait", class_uses($entity)))
                $this->logContext($entity, 'delete');
        }
    }

    private function logContext($context, $action)
    {
        // First, ignore if it's meant to be ignored.
        if ($context->doNotLog())
            return;
        // Then, check if the owner is set for removal. 
        $owner = $context->getOwner();
        if ($this->ouw->isScheduledForDelete($owner)) {
            // TODO: Get all logged contexts and remove'm. 
            // error:logging is to see if it even will work.
error_log("Nag nag gotta add removal for contexts on " . get_class($owner));
        }

        // Does it have a user?
        $user = $this->token_storage->getToken()->getUser();

        $clog = new ContextLog($context, $action);
        $clog->setUserId($user->getid());
        $this->em->persist($clog);
        $metadata = $this->em->getClassMetadata('BisonLab\CommonBundle\Entity\ContextLog');
        $this->uow->computeChangeSet($metadata, $clog);
        return;
    }
}
