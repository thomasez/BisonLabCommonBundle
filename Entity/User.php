<?php

namespace BisonLab\CommonBundle\Entity;

use FOS\UserBundle\Model\User as BaseUser;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="bisoncommon_user")
 */
class User extends BaseUser
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\ManyToMany(targetEntity="Group")
     * @ORM\JoinTable(name="bisoncommon_users_groups",
     *   joinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="id")},
     *   inverseJoinColumns={@ORM\JoinColumn(name="group_id", referencedColumnName="id")}
     * )
     */
    protected $groups;

    /*
     * Somehow the User Form ends up with calling hasGroup without any argument.
     * So I'll do it like this for now.
     * TODO: Find out why, fix and remove this.
     */
    public function hasGroup($name = null)
    {
        if ($name) return parent::hasGroup($name);
    }

    public function getGroupsAsNamesArray()
    {
        $names = array();
        foreach ($this->getGroups() as $g) {
            $names[] = $g->getName();
        }
        return $names;
    }
}
