<?php
declare(strict_types=1);

namespace AuttajaCmd\Input\InputType\OneOfList;

use AuttajaCmd\Input\InputType\Collection;
use AuttajaCmd\Input\InputType\InputType;

class Option
{
	private $text;
	private $value;
	/** @var Collection */
	private $ifSelected;

	public function __construct(string $text)
	{
		$this->text       = $text;
		$this->ifSelected = new Collection();
	}

	/**
	 * If a setter is used, this is the value that is going to be passed to the setter.
	 */
	public function setValue($value) : self
	{
		$this->value = $value;

		return $this;
	}

	public function setIfSelected(Collection $ifSelected) : self
	{
		$this->ifSelected = $ifSelected;

		return $this;
	}

	public function addIfSelected(InputType $inputType) : self
	{
		$this->ifSelected = $this->ifSelected->add($inputType);

		return $this;
	}

	public function text() : string
	{
		return $this->text;
	}

	public function value()
	{
		return $this->value;
	}

	public function hasFollowUpQuestions() : bool
	{
		return ! $this->ifSelected->isEmpty();
	}

	public function followUpQuestions() : Collection
	{
		return $this->ifSelected;
	}
}