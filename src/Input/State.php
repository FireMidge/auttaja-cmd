<?php
declare(strict_types=1);

namespace AuttajaCmd\Input;

class State
{
    public const BUCKET_ENVVARS = 'envVars';

    private $values = [];

    // Maybe rename to addValue(). If we were to return a different instance, it'd break Env\Reader.
    public function withValue(string $key, $value, ?string $bucketName = null) : State
    {
        if ($bucketName) {
            if (! array_key_exists($bucketName, $this->values)) {
                $this->values[$bucketName] = [];
            }

            $this->values[$bucketName][$key] = $value;
        } else {
            $this->values[$key] = $value;
        }

        return $this;
    }

    public function getValuesFromBucket(string $bucketName) : array
    {
        if (! array_key_exists($bucketName, $this->values)) {
            return [];
        }

        return $this->values[$bucketName];
    }

    public function getValue(string $key, ?string $bucketName = null)
    {
        if ($bucketName) {
           $bucketValues = $this->getValuesFromBucket($bucketName);
           return $bucketValues[$key] ?? null;
        }

        return $this->values[$key] ?? null;
    }
}