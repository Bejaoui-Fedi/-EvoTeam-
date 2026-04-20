<?php

namespace App\ProCalendarBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;

class ProCalendarExtension extends Extension implements PrependExtensionInterface
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        // No services to load for now as we use autowiring for controller
    }

    public function prepend(ContainerBuilder $container): void
    {
        // Automagically register the @ProCalendar twig namespace
        $container->prependExtensionConfig('twig', [
            'paths' => [
                '%kernel.project_dir%/src/ProCalendarBundle/Resources/views' => 'ProCalendar',
            ],
        ]);
    }
}
