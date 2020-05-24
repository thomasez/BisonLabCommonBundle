<?php

namespace BisonLab\CommonBundle\Menu;

use Knp\Menu\FactoryInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

/*
 * This can be used by itself or called from another builder.
 * If you do the latter, remember to send a menu object and the container.
 * (It's not injected when you do.)
 */
class Builder implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    public function userMenu(FactoryInterface $factory, array $options)
    {
        $menu = $container = null;
        if (isset($options['menu'])) {
            $menu = $options['menu'];
        } else {
            $menu = $factory->createItem('root');
        }
        if (isset($options['container'])) {
            $container = $options['container'];
        } else {
            $container = $this->container;
        }
        $user = $container->get('security.token_storage')->getToken()->getUser();
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
