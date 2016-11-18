<?php

namespace BisonLab\CommonBundle\Menu;

use Knp\Menu\FactoryInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class Builder implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    public function userMenu(FactoryInterface $factory, array $options)
    {
        $menu = $factory->createItem('root');

        $user = $this->container->get('security.token_storage')->getToken()->getUser();
        $username = $user->getUserName();

        $menu->addChild($username);
        $menu[$username]->addChild('Profile', array('route' => 'fos_user_profile_show'));
        $menu[$username]->addChild('Change Password', array('route' => 'fos_user_change_password'));
        $menu[$username]->addChild('Log out', array('route' => 'fos_user_security_logout'));

        return $menu;
    }
}
