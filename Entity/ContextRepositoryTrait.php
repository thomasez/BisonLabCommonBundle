<?php

namespace BisonLab\CommonBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

trait ContextRepositoryTrait
{
    /* 
     * I'll make this one private since this is called from the Repo traiting
     * this. 
     */
    public function _getOneByContext($owner_class, $system, $object_name, $external_id, $hydrationMode = \Doctrine\ORM\Query::HYDRATE_OBJECT)
    {
        // This is so annoyng! I Just did not get subselects working, at all.
        $qb = $this->_em->createQueryBuilder();
        $qb->select('oc')
              ->from($owner_class, 'oc')
              ->where('oc.system = :system')
              ->andWhere('oc.object_name = :object_name')
              ->andWhere('oc.external_id = :external_id')
              ->setParameter("system", $system)
              ->setParameter("object_name", $object_name)
              ->setParameter("external_id", $external_id)
              ->setMaxResults(1);

        $context = $qb->getQuery()->getResult();

        if (empty($context)) { return null; }
        return current($context)->getOwner();
    }
}
