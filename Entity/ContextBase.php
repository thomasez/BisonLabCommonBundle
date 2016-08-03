<?php

namespace BisonLab\CommonBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/** @ORM\MappedSuperclass */
class ContextBase
{
    use ContextBaseTrait;

    public function __construct($options = array())
    {
        return $this->traitConstruct($options);
    }
}
