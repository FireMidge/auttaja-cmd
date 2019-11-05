<?php

namespace AuttajaCmd\Input;

use AuttajaCmd\Input\InputType\Collection;
use AuttajaCmd\Input\InputType\InputType;

class InputProcessor
{
    public function process(Collection $collection, State $state) : State
    {
        /** @var InputType $question */
        foreach ($collection->toArray() as $question) {
            if (! $question->shouldAskForInput() && $question->hasDefault()) {
                // If we don't need the user to input anything, save the default against the options.
                $state = $question->saveDefault($state);

                $default = $question->default($state);
                if ($question->hasFollowUpQuestions($default)) {
                    $state = $this->process($question->followUpQuestions($default), $state);
                }

                continue;
            }

            echo PHP_EOL . $question->render() . PHP_EOL;

            while (true) {
                $input = $this->getUserInput();
                $error = $question->errorForInput($input);

                if ($error) {
                    fwrite(
                        STDERR,
                        $error . PHP_EOL
                    );
                    continue;
                }

                $state = $question->saveInput($state, $input);

                if ($question->hasFollowUpQuestions($input)) {
                    $state = $this->process($question->followUpQuestions($input), $state);
                }

                break;
            }
        }

        return $state;
    }

    private function getUserInput()
    {
        $input = trim(fgets(STDIN));

        return $input;
    }
}