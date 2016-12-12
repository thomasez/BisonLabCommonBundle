<?php

namespace BisonLab\CommonBundle\Menu;

use Knp\Menu\FactoryInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

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

        $menu->addChild($username);
        $menu[$username]->addChild('Profile', array('route' => 'fos_user_profile_show'));
        $menu[$username]->addChild('Change Password', array('route' => 'fos_user_change_password'));
        $menu[$username]->addChild('Log out', array('route' => 'fos_user_security_logout'));
        return $menu;
    }
}
