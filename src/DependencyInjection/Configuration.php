<?php
namespace Oka\CORSBundle\DependencyInjection;

use Oka\CORSBundle\CorsOptions;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/configuration.html}
 */
class Configuration implements ConfigurationInterface
{
	/**
	 * {@inheritdoc}
	 */
	public function getConfigTreeBuilder()
	{
		$treeBuilder = new TreeBuilder('oka_cors');
		
		if (true === method_exists($treeBuilder, 'getRootNode')) {
			$rootNode = $treeBuilder->getRootNode();
		} else {
			// BC layer for symfony/config 4.1 and older
			$rootNode = $treeBuilder->root('oka_cors');
		}
		
		$rootNode
				->requiresAtLeastOneElement()
				->useAttributeAsKey('name')
				->prototype('array')
					->children()
						->scalarNode(CorsOptions::PATTERN)->defaultNull()->end()
						
						->arrayNode(CorsOptions::ORIGINS)
							->performNoDeepMerging()
							->prototype('scalar')->end()
						->end()
						
						->arrayNode(CorsOptions::ALLOW_METHODS)
							->performNoDeepMerging()
							->prototype('scalar')->end()
						->end()
						
						->arrayNode(CorsOptions::ALLOW_HEADERS)
							->performNoDeepMerging()
							->prototype('scalar')->end()
						->end()
						
						->booleanNode(CorsOptions::ALLOW_CREDENTIALS)->defaultFalse()->end()
						
						->arrayNode(CorsOptions::EXPOSE_HEADERS)
							->performNoDeepMerging()
							->prototype('scalar')->end()
						->end()
						
						->integerNode(CorsOptions::MAX_AGE)->defaultValue(3600)->end()
					->end()
				->end();
		
		return $treeBuilder;
	}
}
