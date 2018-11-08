<?php

namespace Vairogs\Utils\Captcha\Validator\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 * @Target("PROPERTY")
 */
class IsTrue extends Constraint
{
    /**
     * The reCAPTCHA validation message
     */
    public $message = 'vairogs.utils.captcha.recaptcha.validator.message';
    public $invalidHostMessage = 'vairogs.utils.captcha.recaptcha.validator.invalid_host_message';

    /**
     * {@inheritdoc}
     */
    public function getTargets()
    {
        return Constraint::PROPERTY_CONSTRAINT;
    }

    /**
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * @return string
     */
    public function getInvalidHostMessage(): string
    {
        return $this->invalidHostMessage;
    }
}
