<?php

namespace BisonLab\CommonBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

trait ContextRepositoryTrait
{
    public function getOneByContext($system, $object_name, $external_id)
    {
        return $this->findOneByContext($system, $object_name, $external_id);
    }

    // This is the correct name accortding to many.
    public function findOneByContext($system, $object_name, $external_id)
    {
        return current($this->findByContext($system, $object_name, $external_id));
    }

    public function findByContext($system, $object_name, $external_id)
    {
        $qb2 = $this->_em->createQueryBuilder();
        $qb2->select('e')
              ->from($this->_entityName, 'e')
              ->innerJoin('e.contexts', 'ec')
              ->where('ec.system = :system')
              ->andWhere('ec.object_name = :object_name')
              ->andWhere('ec.external_id = :external_id')
              ->setParameter("system", $system)
              ->setParameter("object_name", $object_name)
              ->setParameter("external_id", $external_id);
        return $qb2->getQuery()->getResult();
    }
}
