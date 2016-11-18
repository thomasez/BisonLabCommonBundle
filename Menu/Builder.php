<?php

namespace BisonLab\CommonBundle\Menu;

use Knp\Menu\FactoryInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class Builder implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    private $local_builder = null;

    public function __construct()
    {
        // Both does not exist.
        // (I use AppBundle and LocalBundle here and there..)
        if (class_exists('LocalBundle\Menu\Builder')) {
            $this->local_builder = new \LocalBundle\Menu\Builder();
        } elseif (class_exists('AppBundle\Menu\Builder')) {
            $this->local_builder = new \AppBundle\Menu\Builder();
        }
    }

    public function userMenu(FactoryInterface $factory, array $options)
    {
        $menu = $factory->createItem('root');

        $user = $this->container->get('security.token_storage')->getToken()->getUser();
        $username = $user->getUserName();

        $menu->addChild($username);
        $menu[$username]->addChild('Profile', array('route' => 'fos_user_profile_show'));
        $menu[$username]->addChild('Change Password', array('route' => 'fos_user_change_password'));
        $menu[$username]->addChild('Log out', array('route' => 'fos_user_security_logout'));

        if ($this->local_builder 
                && method_exists($this->local_builder, "userMenu"))
            return $this->local_builder->userMenu($factory, $options, $menu);
        return $menu;
    }
}
