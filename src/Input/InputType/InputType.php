<?php
declare(strict_types=1);

namespace  AuttajaCmd\Input\InputType;

use AuttajaCmd\Input\State;

interface InputType
{
	public function render() : string;

	public function errorForInput($userInput) : ?string;

    public function saveDefault(State $state) : State;

	public function saveInput(State $options, $userInput) : State;

	public function hasFollowUpQuestions($userInput) : bool;

	public function followUpQuestions($userInput) : Collection;

	public function shouldAskForInput() : bool;

    /**
     * Whether the value of this input should be saved against State, or
     * whether it is only used for 'routing' and then discarded.
     */
	public function shouldSave() : bool;

    /**
     * This is the name of the key the input value will be saved as.
     */
    public function setKeyName(string $keyName) : InputType;

    /**
     * Returns the default value (if any).
     *
     * @return mixed
     */
    public function default(State $state);
}