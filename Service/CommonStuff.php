<?php

namespace BisonLab\CommonBundle\Service;

/*
 */

class CommonStuff
{
    private $user_manager;
    private $token_storage;

    public function __construct($user_manager, $token_storage)
    {
        $this->user_manager  = $user_manager;
        $this->token_storage = $token_storage;
    }

    public function getLoggedInUser()
    {
        if (!$this->token_storage) return null;
        if (!$this->token_storage->getToken()) return null;
        return $this->token_storage->getToken()->getUser();
    }

    public function getUserFromUserId($id)
    {
        return $this->user_manager->findUserBy(array('id'=>$id));
    }

    public function getUserFromUserName($username)
    {
        return $this->user_manager->findUserByUsername($username);
    }

    public function getUserNameFromUserId($userid)
    {
        $user = $this->user_manager->findUserBy(array('id' => $userid));
        if (!$user) return $userid;
        return $user->getUserName();;
    }

    public function getEmailFromUser($user = null)
    {
        if (!$user)
            $user = $this->getLoggedInUser();
        // It may just be an ID.
        if (is_numeric($user)) {
            $user = $this->user_manager->findUserBy(array('id' => $user));
        }
        // Or string?
        if (is_string($user)) {
            $user = $this->user_manager->findUserBy(array('username' => $user));
        }

        if (is_object($user) && method_exists($user, 'getEmail'))
            return $user->getEmail();
        return null;
    }

    public function getMobilePhoneNumberFromUser($user = null)
    {
        if (!$user)
            $user = $this->getLoggedInUser();
        // It may just be an ID.
        if (is_numeric($user)) {
            $user = $this->user_manager->findUserBy(array('id' => $user));
        }
        // Or string?
        if (is_string($user)) {
            $user = $this->user_manager->findUserBy(array('username' => $user));
        }

        if (is_object($user) && method_exists($user, 'getMobilePhoneNumber'))
            return $user->getMobilePhoneNumber();
        if (is_object($user) && method_exists($user, 'getPhoneNumber'))
            return $user->getPhoneNumber();
        return null;
    }
}
