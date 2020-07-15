<?php
declare(strict_types=1);

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
        foreach ($inputTypes as $inputType) {
            if (! $inputType instanceof InputType) {
                throw new \Exception(sprintf(
                    'Item with key "%s" added to InputType collection is not an instance of InputType', $k
                ));
            }

            if ($inputType->shouldAskForInput()) {
                $this->inputTypesToAsk[] = $inputType;
            }
        }

        $this->inputTypes = $inputTypes;
    }

    public function add(InputType $input) : self
    {
        $this->inputTypes[] = $input;

        if ($input->shouldAskForInput()) {
            $this->inputTypesToAsk[] = $input;
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
        return new Collection(array_merge($this->inputTypes, $collection->toArray()));
    }

    public function requiresUserInput() : bool
    {
        return (! empty($this->inputTypesToAsk));
    }
}