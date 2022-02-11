<?php

namespace Oka\CORSBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * @author Cedrick Oka Baidai <okacedrick@gmail.com>
 */
class OkaCORSExtension extends Extension
{
	public function load(array $configs, ContainerBuilder $container)
	{
		$loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../../config'));
		$loader->load('services.yml');
		
		$configuration = new Configuration();
		$config = $this->processConfiguration($configuration, $configs);
		
		$definition = $container->getDefinition('oka_cors.request.event_listener');
		$definition->replaceArgument(0, $config);
	}
}
