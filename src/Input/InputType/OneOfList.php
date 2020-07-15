<?php
declare(strict_types=1);

namespace AuttajaCmd\Input\InputType;

use AuttajaCmd\Input\InputType\OneOfList\Option;
use AuttajaCmd\Input\State;

/**
 * Use this when wanting to allow the user to choose one of a list of options.
 */
class OneOfList implements InputType
{
	private $text;
    private $keyName;
    private $default;
	private $options      = [];
	private $asksForInput = true;
	private $shouldSave   = true;

	//private $defaultValueProcessor;

	public function __construct(string $text, array $options)
	{
		$this->text    = $text;
		$this->options = $options;

		//$this->defaultValueProcessor = new DefaultValueProcessor();
	}

	public function render() : string
	{
		$string = $this->text . PHP_EOL;

		/**
		 * @var string $_shortCut
		 * @var \AuttajaCmd\Input\InputType\OneOfList\Option $_option
		 */
		foreach ($this->options as $_shortCut => $_option) {
			$string .= sprintf('%s: %s', $_shortCut, $_option->text()) . PHP_EOL;
		}

		return $string;
	}

	public function errorForInput($userInput) : ?string
	{
		if (! array_key_exists($userInput, $this->options)) {
			return sprintf('Option "%s" not recognised. Please try again', $userInput);
		}

		return null;
	}

    public function saveDefault(State $state) : State
    {
        return $this->saveValue($state, $this->default($state));
    }

    public function saveInput(State $state, $userInput) : State
	{
		return $this->saveValue($state, $userInput);
	}

	public function hasFollowUpQuestions($userInput) : bool
	{
		/** @var Option $selectedOption */
		$selectedOption = $this->options[$userInput];

		return $selectedOption->hasFollowUpQuestions();
	}

	public function followUpQuestions($userInput) : Collection
	{
		/** @var Option $selectedOption */
		$selectedOption = $this->options[$userInput];

		return $selectedOption->followUpQuestions();
	}

    /**
     * Set a default option if no user input is required or the user hasn't selected anything.
     *
     * @param string|int $defaultOption The key of the option to default to.
     *                                  (Possible options are the indices of $options).
     *
     * @return $this
     * @throws \Exception  If the default option is not part of the option set.
     */
	public function setDefault($defaultOption) : self
    {
        if (! array_key_exists($defaultOption, $this->options)) {
            throw new \Exception(sprintf(
                '"%s" is an invalid default option for "%s". Possible options are: %s',
                (string) $defaultOption,
                $this->text,
                PHP_EOL . '"' . implode('", "', $this->options) . '"'
            ));
        }

        $this->default = $defaultOption;


        return $this;
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
        return $this->default;
    }

    public function setShouldSave(bool $shouldSave) : self
    {
        $this->shouldSave = $shouldSave;

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
     * If set to false, the user input will not be saved - which also has a valid use case.
     */
    public function shouldSave() : bool
    {
        return $this->shouldSave;
    }

    private function saveValue(State $state, $value) : State
    {
        if (! $this->shouldSave()) {
            return $state;
        }

        /** @var Option $selectedOption */
        $selectedOption = $this->options[$value];

        return $state->withValue($this->keyName, $selectedOption->value());
    }
}