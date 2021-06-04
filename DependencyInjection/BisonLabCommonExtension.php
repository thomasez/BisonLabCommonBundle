<?php

namespace BisonLab\CommonBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration
 *
 */
class BisonLabCommonExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $config = $this->processConfiguration($this->getConfiguration($configs, $container), $configs);

        $loader = new Loader\YamlFileLoader($container,
            new FileLocator(array(
                __DIR__.'/../Resources/config',
                $container->getParameter('kernel.project_dir').'/app/config/',
                // Kinda forward compatibility.
                $container->getParameter('kernel.project_dir').'/config/packages'
                )
            ));
        $loader->load('services.yml');
        $loader->load('contexts.yml');
    }
}
