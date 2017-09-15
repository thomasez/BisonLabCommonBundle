<?php

namespace BisonLab\CommonBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/*
 * Remember to put this in the owner Entity:
 * use You\YourBundle\Entity\WhateverContext as Context;
 */

trait ContextOwnerTrait
{

    /*
     * This has to be pasted into the owner object, since it's a good thing  to
     * keep the naming correct.
     * s/whatever/realname/g 
     * (remember to add the slash and asterixes..)
     * @ORM\OneToMany(targetEntity="WhateverContext", mappedBy="whatever", cascade={"persist", "remove"})
    private $contexts;
     */

    /* 
     * This could also be solved with keeping __construct() and then
     * use ContextOwnerTrait { __construct as traitConstruct }
     * but I cannot see why it's better. To me it's more confusing.
     */
    public function traitConstruct($options = array())
    {
        $this->contexts = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Get contexts
     *
     * @return objects
     */
    public function getContexts()
    {
        return $this->contexts;
    }

    /**
     * Add contexts
     *
     * @param Context $context;
     * @return Message
     */
    public function addContext(Context $context)
    {
        $this->contexts[] = $context;
        $context->setOwner($this);
        return $this;
    }

    /**
     * Remove context
     *
     * @param Context $context;
     */
    public function removeContext(Context $context)
    {
        $this->contexts->removeElement($context);
    }

}
