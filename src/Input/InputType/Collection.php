<?php

namespace AuttajaCmd\Input\InputType;

/**
 * A collection of input types.
 */
class Collection
{
    private $inputTypes      = [];
    private $inputTypesToAsk = [];

    public function __construct(array $inputTypes = [])
    {
        foreach ($inputTypes as $k => $inputType) {
            if (! $inputType instanceof InputType) {
                throw new \Exception(sprintf(
                    'Item with key "%s" added to InputType collection is not an instance of InputType', $k
                ));
            }

            if ($inputType->shouldAskForInput()) {
                $this->inputTypesToAsk[$k] = $inputType;
            }
        }

        $this->inputTypes = $inputTypes;
    }

    public function add(string $questionText, InputType $input) : self
    {
        $this->inputTypes[$questionText] = $input;

        if ($input->shouldAskForInput()) {
            $this->inputTypesToAsk[$questionText] = $input;
        }

        return $this;
    }

    public function toArray() : array
    {
        return $this->inputTypes;
    }

    public function isEmpty() : bool
    {
        return empty($this->inputTypes);
    }

    public function merge(Collection $collection) : self
    {
        return new Collection($this->inputTypes + $collection->toArray());
    }

    /**
     * Prefixes all keys (input names) with the given string.
     * This may be needed when trying to merge two Collections that share the same keys.
     */
    public function withKeyPrefix(string $prefixForKey) : self
    {
        $self = new static();
        foreach ($this->inputTypes as $k => $inputType) {
            $self = $self->add($prefixForKey . $k, $inputType);
        }

        return $self;
    }

    public function requiresUserInput() : bool
    {
        return (! empty($this->inputTypesToAsk));
    }
}