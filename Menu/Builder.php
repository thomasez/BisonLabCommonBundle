<?php

namespace BisonLab\CommonBundle\Menu;

use Knp\Menu\FactoryInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

/*
 * This can be used by itself or called from another builder.
 * (It's not injected when you do.)
 */
class Builder implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    public function userMenu(FactoryInterface $factory, array $options)
    {
        $menu = null;
        $menu = $options['menu'] ?? $factory->createItem('root');
        $user = $options['user'];

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
