<?php

namespace Vairogs\Utils\Captcha\Validator\Constraint;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\ValidatorException;

class IsTrueValidator extends ConstraintValidator
{
    /**
     * The reCAPTCHA server URL's
     */
    public const RECAPTCHA_VERIFY_SERVER = 'https://www.google.com';

    /**
     * @var string
     */
    protected $secretKey;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var array
     */
    protected $httpProxy;

    /**
     * @var bool
     */
    protected $verifyHost;

    /**
     * @var bool
     */
    protected $validate;

    /**
     * Construct.
     *
     * @param string $secretKey
     * @param array $httpProxy
     * @param bool $verifyHost
     * @param bool $validate
     */
    public function __construct($secretKey, array $httpProxy, $verifyHost, $validate = true)
    {
        $this->secretKey = $secretKey;
        $this->httpProxy = $httpProxy;
        $this->verifyHost = $verifyHost;
        $this->validate = $validate;
    }

    /**
     * {@inheritdoc}
     * @throws ValidatorException
     */
    public function validate($value, Constraint $constraint): void
    {
        $remoteip = $this->request->getClientIp();
        $response = $this->request->get('g-recaptcha-response');
        $isValid = $this->checkAnswer($this->secretKey, $remoteip, $response);
        /** @var IsTrue $constraint */
        if ($isValid['success'] !== true) {
            $this->context->addViolation($constraint->getMessage());
        } elseif ($this->verifyHost && $isValid['hostname'] !== $this->request->getHost()) {
            $this->context->addViolation($constraint->getInvalidHostMessage());
        }
    }

    /**
     * @param string $secret
     * @param string $remoteip
     * @param string $response
     *
     * @return array|bool|mixed
     * @throws ValidatorException
     */
    private function checkAnswer($secret = '', $remoteip = null, $response = null)
    {
        if ($this->validate === false) {
            return ['success' => true];
        }
        if (empty($remoteip)) {
            throw new ValidatorException('vairogs.utils.captcha.recaptcha.validator.remote_ip');
        }
        if (empty($response)) {
            return false;
        }

        return \json_decode($this->httpGet(self::RECAPTCHA_VERIFY_SERVER, '/recaptcha/api/siteverify', \get_defined_vars()), true);
    }

    /**
     * @param string $host
     * @param string $path
     * @param array $data
     *
     * @return string
     */
    private function httpGet($host, $path, $data): string
    {
        return \file_get_contents(\sprintf('%s%s?%s', $host, $path, \http_build_query($data, null, '&')), false, $this->getResourceContext());
    }

    /**
     * @return resource
     */
    private function getResourceContext()
    {
        if (null === $this->httpProxy['host'] || null === $this->httpProxy['port']) {
            return null;
        }
        $options = [];
        // @formatter:off
        foreach (['http', 'https',] as $protocol) {
        // @formatter:on
            $options[$protocol] = [
                'method' => 'GET',
                'proxy' => \sprintf('tcp://%s:%s', $this->httpProxy['host'], $this->httpProxy['port']),
                'request_fulluri' => true,
            ];
            if (null !== $this->httpProxy['auth']) {
                $options[$protocol]['header'] = \sprintf('Proxy-Authorization: Basic %s', \base64_encode($this->httpProxy['auth']));
            }
        }

        return \stream_context_create($options);
    }

    /**
     * @param RequestStack $requestStack
     */
    public function setRequest(RequestStack $requestStack): void
    {
        $this->request = $requestStack->getCurrentRequest();
    }
}
