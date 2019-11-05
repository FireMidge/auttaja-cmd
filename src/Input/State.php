<?php

namespace AuttajaCmd\Input;

class State
{
    public const BUCKET_ENVVARS = 'envVars';

    private $values = [];

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

    public function getValuesFromBucket(string $bucketName) : ?array
    {
        if (! array_key_exists($bucketName, $this->values)) {
            return null;
        }

        return $this->values[$bucketName];
    }

    public function getValue(string $key)
    {
        return $this->values[$key] ?? null;
    }
}