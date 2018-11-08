<?php

namespace Vairogs\Utils\Captcha\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Vairogs\Utils\DependencyInjection\Component\Definable;

class Definition implements Definable
{
    private const ALLOWED = [
        Definable::CAPTCHA,
    ];

    public function getExtensionDefinition($extension): ArrayNodeDefinition
    {
        if (!\in_array($extension, self::ALLOWED, true)) {
            throw new InvalidConfigurationException(\sprintf('Invalid extension: %s', $extension));
        }
        $treeBuilder = new TreeBuilder();
        $node = $treeBuilder->root(Definable::CAPTCHA);
        /** @var ArrayNodeDefinition $node */
        // @formatter:off
        $node
            ->canBeEnabled()
            ->addDefaultsIfNotSet()
            ->children()
                ->arrayNode('clients')
                    ->arrayPrototype()
                        ->variablePrototype()->end()
                    ->end()
                ->end()
            ->end();
        // @formatter:on
        return $node;
    }
}
