<?php

namespace Vairogs\Utils\Captcha\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Exception\AccessException;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Vairogs\Utils\Captcha\Router\LocaleResolver;

class RecaptchaType extends AbstractType
{
    /**
     * The reCAPTCHA Server URL's
     */
    public const RECAPTCHA_API_SERVER = 'https://www.google.com/recaptcha/api';
    public const RECAPTCHA_API_JS_SERVER = 'https://www.google.com/recaptcha/api/js/recaptcha_ajax.js';
    public $scripts = [];
    /**
     * @var string
     */
    protected $publicKey;
    /**
     * @var bool
     */
    protected $ajax;
    /**
     * @var LocaleResolver
     */
    protected $localeResolver;
    /**
     * @var array
     */
    protected $options = [];

    /**
     * @param string $publicKey
     * @param bool $ajax
     * @param array $options
     */
    public function __construct($publicKey, $ajax, array $options = [])
    {
        $this->publicKey = $publicKey;
        $this->ajax = $ajax;
        $this->options = $options;
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars = \array_replace($view->vars, [
            'recaptcha_ajax' => $this->ajax,
            'public_key' => $this->publicKey,
        ]);
        if (!$this->ajax) {
            $view->vars = \array_replace($view->vars, [
                'url_challenge' => \sprintf('%s.js?hl=%s', self::RECAPTCHA_API_SERVER, $options['language']),
            ]);
        } else {
            $view->vars = \array_replace($view->vars, [
                'url_api' => self::RECAPTCHA_API_JS_SERVER,
            ]);
        }
        if (!empty($this->options)) {
            $this->parseOptions($view);
        }
    }

    private function parseOptions(FormView $view): void
    {
        $baseOptions = [
            'compound',
            'url_challenge',
            'url_noscript',
        ];
        $attributes = [
            'theme',
            'type',
            'size',
            'callback',
            'expiredCallback',
            'defer',
            'async',
        ];
        foreach ($baseOptions as $option) {
            if (isset($this->options[$option])) {
                $view->vars[$option] = $this->options[$option];
            }
        }
        foreach ($attributes as $attribute) {
            if (isset($this->options[$attribute])) {
                $view->vars['attr']['options'][$attribute] = $this->options[$attribute];
            }
        }
    }

    /**
     * {@inheritdoc}
     * @throws AccessException
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'compound' => false,
            'language' => $this->localeResolver->resolve(),
            'public_key' => null,
            'url_challenge' => null,
            'url_noscript' => null,
            'attr' => [
                'options' => [
                    'theme' => 'light',
                    'type' => 'image',
                    'size' => 'normal',
                    'callback' => null,
                    'expiredCallback' => null,
                    'defer' => false,
                    'async' => false,
                ],
            ],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix(): string
    {
        return 'kode_cms_kode_captcha_recaptcha';
    }

    /**
     * @param string $key
     *
     * @return string|null
     */
    public function getScriptURL($key): ?string
    {
        return $this->scripts[$key] ?? null;
    }

    /**
     * @return string|null
     */
    public function getpublicKey(): ?string
    {
        return $this->publicKey;
    }

    /**
     * @param LocaleResolver $localeResolver
     */
    public function setLocaleResolver(LocaleResolver $localeResolver): void
    {
        $this->localeResolver = $localeResolver;
    }
}
