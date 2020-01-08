<?php

namespace BisonLab\CommonBundle\Menu;

/*
 * This should somehow be able to get the preferred menu style and use it.
 *
 * For now it's my preferred style, bootstrap tabs.
 *
 * You need to add this to config.yml / .env
    knp_menu:
        renderer.twig.options:
            currentClass: active
            ancestorClass: active
            allow_safe_labels: true
 */
trait StylingTrait
{
    public function styleMenuBootstrapTabs($menu, $options)
    {
        $menu->setChildrenAttribute('class', 'nav nav-tabs');
        foreach ($menu->getChildren() as $child) {
            // Why is this not working?
            // Solution:
            if ($child->isCurrent()) {
                $child->setAttribute('class', 'active');
            }
            if (count($child->getChildren()) > 0) {
                $child
                    ->setLabel($child->getLabel() . '<b class="caret"></b>')
                    ->setExtra('safe_label', true)
                    ->setUri('#')
                    ->setLinkAttribute('class', 'nav-link dropdown-toggle')
                    ->setLinkAttribute('data-toggle', 'dropdown')
                    ->setLinkAttribute('role', 'button')
                    ->setLinkAttribute('aria-haspopup', 'true')
                    ->setLinkAttribute('aria-expanded', 'false')
                    ->setAttribute('class', 'nav-item dropdown')
                    ->setChildrenAttribute('class', 'dropdown-menu');
                foreach ($child->getChildren() as $c_child) {
                    $c_child
                        ->setLinkAttribute('class', 'dropdown-item')
                        ->setAttribute('class', 'nav-item');
                }
            } else {
                $child->setLinkAttribute('class', 'nav-link')
                    ->setAttribute('class', 'nav-item');
            }
        }
        return $menu;
    }
}
