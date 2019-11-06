<?php

namespace AuttajaCmd\Input\InputType;

use AuttajaCmd\Input\State;

/**
 * Use this when allowing the user to enter free text, and then optionally verifying the input.
 * The user may need a lot of hints on what to enter.
 */
class Any implements InputType
{
    public const VAR_TYPE_STRING  = 'string';
    public const VAR_TYPE_INTEGER = 'integer';
    public const VAR_TYPE_INT     = 'int';

    private const VAR_TYPES = [
        self::VAR_TYPE_STRING,
        self::VAR_TYPE_INTEGER,
        self::VAR_TYPE_INT,
    ];

	private $text         = null;
	private $type         = null;
	private $name         = null;
	private $default      = null;
	private $example      = null;
	private $annotations  = [];
	private $bucketName   = null;
	private $keyName      = null;
	private $asksForInput = true;
	private $shouldSave   = true;

	private $defaultValueProcessor;

	public function render() : string
	{
		$string = !empty($this->annotations)
			? implode(PHP_EOL, $this->annotations) . PHP_EOL
			: '';

		$string .= $this->text;

		return $string;
	}

	/**
	 * @param string  $text  The question text.
	 */
	public function __construct(string $text)
	{
		$this->text = $text;

		$this->defaultValueProcessor = new DefaultValueProcessor();
	}

	/**
	 * Overwrite the question text.
	 */
	public function setText(string $text) : self
	{
		$this->text = $text;

		return $this;
	}

    /**
     * This is the name of the key the input value will be saved as.
     * If no key name is set, the user input will not be saved - which also has a valid use case.
     */
    public function setBucketName(?string $bucketName) : InputType
    {
        $this->bucketName = $bucketName;

        return $this;
    }

    /**
     * This is the name of the key the input value will be saved as.
     */
    public function setKeyName(string $keyName) : InputType
    {
        $this->keyName = $keyName;

        return $this;
    }

	/**
	 * The variable type, e.g. "integer", "string".
	 */
	public function setType(?string $type) : self
	{
	    if ($type !== null && ! in_array($type, self::VAR_TYPES, true)) {
            throw new \Exception(sprintf(
                'Unknown type "%s" (used by "%s"). Valid types are: %s',
                $type,
                $this->text,
                implode(', ', self::VAR_TYPES)
            ));
        }

		$this->type = $type;

		return $this;
	}

	/**
	 * The name of the variable we're setting.
     * TODO: Can probably get rid of this?
	 */
	public function setName(?string $name) : self
	{
		$this->name = $name;

		return $this;
	}

	/**
	 * This is a shell command, whose output is going to be used as a default value for the environment variable.
	 */
	public function setDefault(?string $default) : self
	{
		$this->default = $default;

		return $this;
	}

	/**
	 * This is just to give the user an idea of what kind of value to enter.
	 */
	public function setExample(?string $example) : self
	{
		$this->example = $example;

		return $this;
	}

	/**
	 * One or more lines of annotations for the variable to enter.
	 */
	public function setAnnotations(array $annotations) : self
	{
		$this->annotations = $annotations;

		return $this;
	}

	public function addAnnotation(string $annotation) : self
	{
		$this->annotations[] = $annotation;

		return $this;
	}

	public function errorForInput($userInput) : ?string
	{
		if (! $this->type) {
			// We don't validate if no type has been set.
			return null;
		}

		if (strlen($userInput) === 0) {
			// Maybe we'd want to disallow empty input based on an option. (TODO)
			return null;
		}

		switch ($this->type) {
            case self::VAR_TYPE_INTEGER:
            case self::VAR_TYPE_INT:
				if (! is_numeric($userInput)) {
					return 'Input has to be an integer. Please try again.';
				}
				break;

			// TODO: Add more
		}

		return null;
	}

	public function saveDefault(State $state) : State
    {
        return $this->saveValue($state, $this->default($state));
    }

	public function saveInput(State $state, $userInput) : State
	{
		if (strlen($userInput) === 0 && $this->default !== null) {
			$userInput = $this->default($state);
		}

		return $this->saveValue($state, $userInput);
	}

	public function hasFollowUpQuestions($userInput) : bool
	{
		// Follow-up questions are not supported for this input type.
		return false;
	}

	public function followUpQuestions($userInput) : Collection
	{
		// Follow-up questions are not supported for this input type.
		return new Collection();
	}

	public function example() : ?string
	{
		return $this->example;
	}

	public function hasDefault() : bool
	{
		return (bool) $this->default;
	}

	public function setShouldAskForInput(bool $askForInput)
    {
        $this->asksForInput = $askForInput;
    }

	public function shouldAskForInput() : bool
    {
        return $this->asksForInput;
    }

    public function default(State $state)
    {
        return $this->defaultValueProcessor->process($this->default, $state, 'shell');
    }

    public function shouldSave() : bool
    {
        return $this->shouldSave;
    }

    private function saveValue(State $state, $value) : State
    {
        if (! $this->shouldSave()) {
            return $state;
        }

        switch ($this->type) {
            case null: break;

            case 'int':
            case 'integer':
                $value = (int)$value;
                break;
        }

        return $state->withValue($this->keyName, $value, $this->bucketName);
    }
}