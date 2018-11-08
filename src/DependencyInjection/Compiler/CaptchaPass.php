<?php

namespace Vairogs\Utils\Captcha\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Vairogs\Utils\DependencyInjection\Component\Definable;
use Vairogs\Utils\VairogsBundle;

class CaptchaPass implements CompilerPassInterface
{
    public const NAME = \VAIROGS.'.'.VairogsBundle::ALIAS.'.'.Definable::CAPTCHA;

    /**
     * {@inheritdoc}
     * @throws InvalidArgumentException
     */
    public function process(ContainerBuilder $container): void
    {
        if (VairogsBundle::isEnabled($container, Definable::CAPTCHA)) {
            $resources = $container->getParameter('twig.form.resources');
            $form = \sprintf('%s.client.recaptcha.template', self::NAME);
            $check = \sprintf('%s.enabled', self::NAME);
            if ($container->hasParameter($check) && $container->getParameter($check) === true && $container->hasParameter($form) && false !== ($template = $container->getParameter($form)) && !\in_array($template, $resources, false)) {
                $resources[] = $template;
            }
            $container->setParameter('twig.form.resources', $resources);
        }
    }
}
