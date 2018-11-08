<?php

namespace Vairogs\Utils\Captcha\DependencyInjection\Configurator;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Vairogs\Utils\Captcha\Form\Type\RecaptchaType;
use Vairogs\Utils\Captcha\Router\LocaleResolver;
use Vairogs\Utils\Captcha\Validator\Constraint\IsTrueValidator;
use Vairogs\Utils\DependencyInjection\Component\Extendable;

class ReCaptchaConfigurator implements Extendable
{
    public function buildClientConfiguration(ArrayNodeDefinition $node): void
    {
        $node->addDefaultsIfNotSet();
        $optionsNode = $node->children();
        // @formatter:off
        $optionsNode
            ->scalarNode('public_key')->isRequired()->cannotBeEmpty()->end()
            ->scalarNode('private_key')->isRequired()->cannotBeEmpty()->end()
            ->booleanNode('verify_host')->defaultValue(false)->end()
            ->booleanNode('ajax')->defaultValue(false)->end()
            ->scalarNode('locale_key')->defaultValue('en')->end()
            ->scalarNode('template')->defaultValue('Captcha/vairogs_utils_captcha_recaptcha_widget.html.twig')->end()
            ->scalarNode('resolver_class')->defaultValue(LocaleResolver::class)->end()
            ->scalarNode('type_class')->defaultValue(RecaptchaType::class)->end()
            ->scalarNode('validator_class')->defaultValue(IsTrueValidator::class)->end()
            ->scalarNode('validate')->defaultValue(true)->end()
            ->arrayNode('locales')
                ->requiresAtLeastOneElement()
                ->beforeNormalization()
                    ->ifString()
                        ->then(function($v) {
                            return \preg_split('/\s*,\s*/', $v);
                        })
                    ->end()
                ->scalarPrototype()->end()
            ->end()
            ->append($this->getHttpProxyNode())
            ->append($this->getOptionsNode())
        ;
        // @formatter:on
        $optionsNode->end();
    }

    private function getHttpProxyNode()
    {
        $builder = new TreeBuilder();
        $node = $builder->root('http_proxy');
        // @formatter:off
        $node
            ->addDefaultsIfNotSet()
            ->children()
                ->scalarNode('host')->defaultValue(null)->end()
                ->scalarNode('port')->defaultValue(null)->end()
                ->scalarNode('auth')->defaultValue(null)->end()
            ->end();
        // @formatter:on
        return $node;
    }

    private function getOptionsNode()
    {
        $builder = new TreeBuilder();
        $node = $builder->root('options');
        // @formatter:off
        $node
            ->addDefaultsIfNotSet()
            ->children()
                ->booleanNode('compound')->defaultValue(false)->end()
                ->scalarNode('url_challenge')->defaultValue(null)->end()
                ->scalarNode('url_noscript')->defaultValue(null)->end()
                ->enumNode('theme')->values(['light', 'dark'])->defaultValue('light')->end()
                ->scalarNode('type')->defaultValue('image')->end()
                ->enumNode('size')->values(['normal', 'compact'])->defaultValue('normal')->end()
                ->scalarNode('callback')->defaultValue(null)->end()
                ->scalarNode('expiredDallback')->defaultValue(null)->end()
                ->booleanNode('defer')->defaultValue(false)->end()
                ->booleanNode('async')->defaultValue(false)->end()
            ->end();
        // @formatter:on
        return $node;
    }

    public function configureClient(ContainerBuilder $container, $clientServiceKey, array $options = []): void
    {
        $this->configureResolver($container, $clientServiceKey, $resolver);
        $this->configureType($container, $clientServiceKey, $resolver);
        $this->configureValidator($container, $clientServiceKey);
    }

    private function configureResolver(ContainerBuilder $container, $clientServiceKey, &$resolver): void
    {
        $resolver = $clientServiceKey.'.resolver';
        $resolverClass = $container->getParameter($clientServiceKey.'.resolver_class');
        $resolverDefinition = $container->register($resolver, $resolverClass);
        $resolverDefinition->setPublic(false);
        $resolverDefinition->setArguments([
            $container->getParameter($clientServiceKey.'.locale_key'),
            $container->getParameter($clientServiceKey.'.locales'),
        ]);
        $resolverDefinition->addMethodCall('setRequest', [new Reference('request_stack')]);
    }

    private function configureType(ContainerBuilder $container, $clientServiceKey, $resolver): void
    {
        $type = $clientServiceKey.'.form.type';
        $typeClass = $container->getParameter($clientServiceKey.'.type_class');
        $typeDefinition = $container->register($type, $typeClass);
        $typeDefinition->setArguments([
            $container->getParameter($clientServiceKey.'.public_key'),
            $container->getParameter($clientServiceKey.'.ajax'),
            $container->getParameter($clientServiceKey.'.options'),
        ]);
        $typeDefinition->addMethodCall('setLocaleResolver', [$container->getDefinition($resolver)]);
        $typeDefinition->addTag('form.type');
    }

    private function configureValidator(ContainerBuilder $container, $clientServiceKey): void
    {
        $validator = $clientServiceKey.'.validator.true';
        $validatorClass = $container->getParameter($clientServiceKey.'.validator_class');
        $validatorDefinition = $container->register($validator, $validatorClass);
        $validatorDefinition->setArguments([
            $container->getParameter($clientServiceKey.'.private_key'),
            $container->getParameter($clientServiceKey.'.http_proxy'),
            $container->getParameter($clientServiceKey.'.verify_host'),
            $container->getParameter($clientServiceKey.'.validate'),
        ]);
        $validatorDefinition->addMethodCall('setRequest', [new Reference('request_stack')]);
        $validatorDefinition->addTag('validator.constraint_validator');
    }
}
