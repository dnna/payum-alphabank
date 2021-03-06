<?php

namespace Dnna\Payum\AlphaBank\Util;

use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Security\SensitiveValue;

final class DigestCalculator
{
    private $sharedSecretKey;

    public function __construct($sharedSecretKey)
    {
        $this->sharedSecretKey = $sharedSecretKey;
    }

    public function calculateDigest(ArrayObject $model): string
    {
        $newModel = clone $model;
        $string = '';
        foreach ($newModel as $curValue) {
            if ($curValue instanceof SensitiveValue) {
                $curValue = $curValue->get();
            }
            $string .= $curValue;
        }
        $string .= $this->sharedSecretKey;

        return base64_encode(hash('sha256', $string, true));
    }

    public function calculateDigestForString(string $string): string
    {
        $string .= $this->sharedSecretKey;

        return base64_encode(hash('sha256', $string, true));
    }

    public function calculateDigestForStringSha1(string $string): string
    {
        $string .= $this->sharedSecretKey;

        return base64_encode(hash('sha1', $string, true));
    }

    public function verifyDigest(array $model, string $digest): bool
    {
        unset($model['digest']);
        $string = '';
        foreach ($model as $curValue) {
            $string .= $curValue;
        }
        $string .= $this->sharedSecretKey;
        $newDigest = base64_encode(hash('sha256', $string, true));

        return $newDigest === $digest;
    }
}
