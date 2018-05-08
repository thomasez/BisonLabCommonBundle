<?php

namespace BisonLab\CommonBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="BisonLab\CommonBundle\Entity\Repository\ContextLogRepository")
 *
 * @ORM\Table(
 *     name="bisoncommon_context_log",
 *  indexes={
 *      @ORM\Index(name="log_owner_lookup_idx", columns={"owner_class", "owner_id"})
 *  }
 * )
 *
 */

class ContextLog
{
    /**
     * @var integer $id
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected $id;

    /**
     * @var string $action
     *
     * @ORM\Column(type="string", length=8)
     */
    protected $action;

    /**
     * @var \DateTime $logged_at
     *
     * @ORM\Column(type="datetime")
     */
    protected $logged_at;

    /**
     * @var string $userid
     * Annoyingly enough, there might not be a (known) user doing this.
     *
     * @ORM\Column(type="string", length=80, nullable=true)
     */
    protected $user_id;

    /**
     * @var string $classname
     *
     * @ORM\Column(type="string", length=255)
     */
    protected $owner_class;

    /**
     * @var int $id
     *
     * @ORM\Column(type="string", length=80)
     */
    private $owner_id;

    /**
     * @var string $system
     *
     * @ORM\Column(type="string", length=255)
     */
    private $system;

    /**
     * @var string $object_name
     *
     * @ORM\Column(type="string", length=255)
     */
    private $object_name;

    /**
     * @var string $external_id
     *
     * @ORM\Column(type="string", length=80)
     */
    private $external_id;

    /**
     * @var string $url
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $url;

    public function __construct($context, $action)
    {
        $this->logged_at = new \DateTime();
        $this->owner_class = $context->getOwnerEntityAlias();
        $owner_entity = $context->getOwner();
        $this->owner_id = $owner_entity->getId();
        $this->system = $context->getSystem();
        $this->object_name = $context->getObjectName();
        $this->external_id = $context->getExternalId();
        $this->url = $context->getUrl();
        $this->action = $action;
        return $this;
    }

    public function setUserId($user_id)
    {
        $this->user_id = $user_id;
    }
}
