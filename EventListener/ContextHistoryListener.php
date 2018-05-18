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

    public function __construct($token_storage, $doctrine)
    {
        $this->token_storage = $token_storage;
        $this->doctrine      = $doctrine;
    }

    public function onFlush(EventArgs $eventArgs)
    {
        $this->em = $eventArgs->getEntityManager();
        $this->uow = $this->em->getUnitOfWork();

        foreach ($this->uow->getScheduledEntityInsertions() as $entity) {
            if (in_array("BisonLab\CommonBundle\Entity\ContextBaseTrait",
                    class_uses($entity)))
                $this->logContext($entity, 'create');
        }
        foreach ($this->uow->getScheduledEntityUpdates() as $entity) {
            if (in_array("BisonLab\CommonBundle\Entity\ContextBaseTrait",
                    class_uses($entity)))
                $this->logContext($entity, 'update');
        }
        foreach ($this->uow->getScheduledEntityDeletions() as $entity) {
            if (in_array("BisonLab\CommonBundle\Entity\ContextBaseTrait",
                    class_uses($entity)))
                $this->logContext($entity, 'delete');
        }
    }

    private function logContext($context, $action)
    {
        // First, ignore if it's meant to be ignored.
        if ($context->doNotLog())
            return;

        // Do I have to check if we've already made a log context for this?
        // I was hoping we didn't and it really should not be like that.

        // Gotta use the correct entity manager
        $bcomm_em = $this->doctrine->getManagerForClass(
            "BisonLabCommonBundle:ContextLog");

        // Then, check if the owner is set for removal. 
        // It may even be disconnected already, so if there are no owner,
        // these has to go.
        // But this may not work out properly. If the relation has been
        // disconnected before we are here, which may be the case, we do not
        // have an owner even if the owner exists.
        $owner = $context->getOwner();
        if ($action == "delete" && 
                (!$owner 
                  || $this->uow->isScheduledForDelete($owner))) {
            // TODO: Get all logged contexts and remove'm. 
            // error:logging is to see if it even will work.
            $log_repo = $bcomm_em->getRepository('BisonLab\CommonBundle\Entity\ContextLog');
            foreach ($log_repo->findBy(array(
                    'owner_class' => $context->getOwnerEntityAlias(),
                    'owner_id' => $owner->getId())) as $clog) {
                $bcomm_em->remove($clog);
                $this->uow->computeChangeSets();
                if (!in_array('BisonLabCommonBundle', array_keys(
                    $this->em->getConfiguration()
                        ->getEntityNamespaces()))) {
                            $bcomm_em->flush();
                }
            }
            return;
        }

        // Does it have a user?
        $user = $this->token_storage->getToken()->getUser();

        $clog = new ContextLog($context, $action);
        $clog->setUserId($user->getid());
        $bcomm_em->persist($clog);
        $metadata = $bcomm_em->getClassMetadata('BisonLab\CommonBundle\Entity\ContextLog');
        $this->uow->computeChangeSet($metadata, $clog);

        // We may be using the same entitiy manager as the object we logged
        // (which is way more common than not). If we then tried to flush it
        // would be an endless loop.
        if (!in_array('BisonLabCommonBundle', array_keys(
            $this->em->getConfiguration()->getEntityNamespaces()))) {
                $bcomm_em->flush($clog);
        }
        return;
    }
}
