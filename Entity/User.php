<?php

namespace RedpillLinpro\CommonBundle\Entity;

use FOS\UserBundle\Model\User as BaseUser;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="rplp_user")
 */
class User extends BaseUser
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    public function __construct()
    {
        parent::__construct();
        // your own logic
    }

    public function getExpiresAt() 
    {
        return $this->expiresAt;
    }

    public function getCredentialsExpireAt() 
    {
        return $this->credentialsExpireAt;
    }

}
