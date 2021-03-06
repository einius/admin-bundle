<?php

/**
 * This file is part of the "NFQ Bundles" package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nfq\AdminBundle\DependencyInjection\Compiler;

use Nfq\AdminBundle\Event\ConfigureMenuEvent;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\ParameterNotFoundException;

/**
 * Class AdminMenuCompilerPass
 * @package Nfq\AdminBundle\DependencyInjection\Compiler
 */
class AdminMenuCompilerPass implements CompilerPassInterface
{
    /**
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        try {
            $config = $container->getParameter('nfq_admin.menu_security');
        } catch(ParameterNotFoundException $e) {
            return;
        }

        foreach ($this->getKernelEventListeners($container) as $id => $service) {
            if ($this->isAdminMenuEvent($service)) {
                $this->addGrantedRoles($container, $id, $config);
            }
        }
    }

    /**
     * @param ContainerBuilder $container
     * @param string           $id
     * @param array            $config
     */
    private function addGrantedRoles($container, $id, $config)
    {
        $bundleNamespace = explode('.', $id)[0];
        if ($this->isBundleConfigDefined($bundleNamespace, $config)) {
            $definition = $container->findDefinition(sprintf('%s.admin_configure_menu_listener', $bundleNamespace));
            if ($definition) {
                $definition->addMethodCall('setGrantedRoles', [$config[$bundleNamespace]]);
            }
        }
    }

    /**
     * Check if its Admin menu configure event
     *
     * @param array
     *
     * @return bool
     */
    private function isAdminMenuEvent($service)
    {
        return (in_array($service[0]['event'], [ConfigureMenuEvent::HEADER_MENU, ConfigureMenuEvent::SIDE_MENU]));
    }

    /**
     * @param ContainerBuilder $container
     *
     * @return array
     */
    private function getKernelEventListeners(ContainerBuilder $container)
    {
        return $container->findTaggedServiceIds('kernel.event_listener');
    }

    /**
     * @param $bundleNamespace
     * @param $config
     *
     * @return bool
     */
    private function isBundleConfigDefined($bundleNamespace, $config)
    {
        return array_key_exists($bundleNamespace, $config);
    }
}
