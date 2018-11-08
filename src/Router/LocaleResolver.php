<?php

namespace Vairogs\Utils\Captcha\Router;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Vairogs\Utils\Core\Router\LocaleResolverTrait;

final class LocaleResolver
{
    use LocaleResolverTrait;

    /**
     * @var string
     */
    private $defaultLocale;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var array
     */
    private $availableLocales;

    /**
     * @param string $defaultLocale
     * @param array $availableLocales
     */
    public function __construct($defaultLocale, array $availableLocales = [])
    {
        $this->defaultLocale = $defaultLocale;
        $this->availableLocales = $availableLocales;
    }

    public function resolve()
    {
        $locale = $this->resolveLocale($this->request, $this->availableLocales);
        if (\in_array($locale, $this->availableLocales, true)) {
            return $locale;
        }

        return $this->defaultLocale;
    }

    /**
     * @param RequestStack $requestStack
     */
    public function setRequest(RequestStack $requestStack): void
    {
        $this->request = $requestStack->getCurrentRequest();
    }
}
