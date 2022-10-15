<?php

namespace BisonLab\CommonBundle\Menu;

use Knp\Menu\FactoryInterface;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/*
 * This can be used by itself or called from another builder.
 */
class Builder
{
    public function __construct(
        private TokenStorageInterface $tokenStorage,
        private ParameterBagInterface $params,
    ) {
    }

    public function userMenu(FactoryInterface $factory, array $options)
    {
        $menu = $container = null;
        if (isset($options['menu'])) {
            $menu = $options['menu'];
        } else {
            $menu = $factory->createItem('root');
        }
        $user = $this->tokenStorage->getToken()->getUser();
        $username = $user->getUserName();

        $usermenu = $menu->addChild($username);
        // This pythonesque way is suggested by the symfony docs.
        try {
            $usermenu->addChild('Profile', array('route' => 'bisonlab_user_profile'));
            $usermenu->addChild('Change Password', array('route' => 'bisonlab_self_change_password'));
            $usermenu->addChild('Log out', array('route' => 'bisonlab_logout'));
        } catch (RouteNotFoundException $e) {
            return $menu;
        }
        return $menu;
    }
}
