<?php

namespace Vairogs\Utils\Captcha\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Vairogs\Utils\Captcha\DependencyInjection\Configurator\ReCaptchaConfigurator;
use Vairogs\Utils\DependencyInjection\Component\Configurable;
use Vairogs\Utils\DependencyInjection\Component\Definable;
use Vairogs\Utils\DependencyInjection\Component\Extendable;

class CaptchaConfiguration implements Configurable, Extendable
{
    public const SUPPORTED = [
        'recaptcha_v2' => ReCaptchaConfigurator::class,
    ];

    public $configurators = [];
    protected $type;
    protected $alias;
    protected $usedTypes = [];

    public function __construct($alias)
    {
        $this->alias = $alias.'.'.Definable::CAPTCHA;
    }

    public function configure(ContainerBuilder $container): void
    {
        $clientConfigurations = $container->getParameter($this->alias.'.clients');
        /** @var $clientConfigurations array */
        foreach ($clientConfigurations as $key => $clientConfig) {
            if (!isset($clientConfig['type'])) {
                throw new InvalidConfigurationException(\sprintf('%s.clients.%s config entry is missing the "type" key.', $this->alias, $key));
            }
            $this->type = $clientConfig['type'];
            unset($clientConfig['type']);
            if (!isset(self::SUPPORTED[$this->type])) {
                $supportedKeys = \array_keys(self::SUPPORTED);
                \sort($supportedKeys);
                throw new InvalidConfigurationException(\sprintf('%s.clients config "type" key "%s" is not supported. Supported: %s', $this->alias, $this->type, \implode(', ', $supportedKeys)));
            }
            if (!\in_array($this->type, $this->usedTypes, true)) {
                $this->usedTypes[] = $this->type;
            } else {
                throw new InvalidConfigurationException(\sprintf('%s.clients config "type" key "%s" is already in use. Only one occurence per type is allowed', $this->alias, $this->type));
            }
            $tree = new TreeBuilder();
            $processor = new Processor();
            $node = $tree->root('vairogs_utils_captcha/clients/'.$key);
            /** @var ArrayNodeDefinition node */
            $this->buildClientConfiguration($node);
            $config = $processor->process($tree->buildTree(), [$clientConfig]);
            $clientServiceKey = $this->alias.'.client.'.$key;
            foreach ($config as $ckey => $cvalue) {
                $container->setParameter($clientServiceKey.'.'.$ckey, $cvalue);
            }
            $this->configureClient($container, $clientServiceKey);
        }
    }

    public function buildClientConfiguration(ArrayNodeDefinition $node): void
    {
        $this->getConfigurator($this->getType())->buildClientConfiguration($node);
    }

    public function getConfigurator($type)
    {
        if (!isset($this->configurators[$type])) {
            $class = self::SUPPORTED[$type];
            $this->configurators[$type] = new $class();
        }

        return $this->configurators[$type];
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param $type
     *
     * @return $this
     */
    public function setType($type): CaptchaConfiguration
    {
        $this->type = $type;

        return $this;
    }

    public function configureClient(ContainerBuilder $container, $clientServiceKey, array $options = []): void
    {
        $this->getConfigurator($this->getType())->configureClient($container, $clientServiceKey, $options);
    }
}
