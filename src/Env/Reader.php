<?php

namespace AuttajaCmd\Env;

use AuttajaCmd\Input\InputType\Any;
use AuttajaCmd\Input\InputType\Collection;
use AuttajaCmd\Input\InputType\OneOfList;
use AuttajaCmd\Input\State;
use Env\Helper;

class Reader
{
    private const MAIN_TEMPLATE_FILE_PATH = '.env.template';
    private const TEST_TEMPLATE_FILE_PATH = '.env.test.template';

    private $helper;

    public function __construct()
    {
        $this->helper = new Helper();
    }

    public function prepareInputs(array $filePaths = []) : Collection
    {
        // TODO: Read the existing .env files first unless we are recreating from scratch (and save them to state)
        // Once we have State, we can also display the default values that are going to be used.
        // Although.. that doesn't work if it relies on a variable that we don't currently have yet (another env var)
        // In order to be able to do that we'd have to write the defaults during execution and not upfront like here,
        // where we are doing it all before we have the user input.
        // TODO: Accept writing only a specific file.

        $setUpQuestions       = $this->getVariablesFromEnvTemplateFile(self::MAIN_TEMPLATE_FILE_PATH);
        $missingTestVariables = $this->getVariablesFromEnvTemplateFile(self::TEST_TEMPLATE_FILE_PATH);

        if (! empty($missingTestVariables)) {
            if ($missingTestVariables->requiresUserInput()) {
                // If any user input is required, we want to start the .env.test set-up with an introductory question.
                // This also gets around duplicate "question texts" (ie. variable names) because the .env.test variables
                // are not stored in the same dimension as the .env variables.
                $setUpQuestions = $setUpQuestions->merge(new Collection([
                    new OneOfList('Would you like to set up the .env.test file now?', [
                        'y' => (new OneOfList\Option('Yes'))->setValue(true)->setIfSelected($missingTestVariables),
                        'n' => (new OneOfList\Option('No'))->setValue(false),
                    ])
                ]));

                // TODO: We currently have no way of setting up the .env.test file later, at least not in an automated way.
            } else {
                // If no user input is required, just process all missing variables as part of the normal .env set-up,
                // which means all the variables will just use a default value.
                // All the test environment variable names are prefixed with 'test.', which doesn't influence
                // the user experience because they are "hidden questions" anyway.
                // We have to prefix them, as otherwise they will clash with the variables in .env.
                $setUpQuestions = $setUpQuestions->merge($missingTestVariables->withKeyPrefix('test.'));
            }
        }

        return new Collection([
            (new OneOfList('Would you like to set up the .env file now?', [
                'y' => (new OneOfList\Option('Yes'))->setValue(true)->setIfSelected($setUpQuestions),
                'n' => (new OneOfList\Option('No'))->setValue(false),
            ])),
        ]);
    }

    private function getVariablesFromEnvTemplateFile(string $path) : Collection
    {
        $fileHandle = fopen($path, 'r');
        $scope      = $this->helper->getEnvVarScopeFromPath($path);

        $variables       = [];
        $currentVariable = null;
        $ignoreLine      = false;

        while (($line = fgets($fileHandle)) !== false)
        {
            $line = trim($line);

            if (strlen($line) === 0) {
                // Empty line; skip.
                continue;
            }

            // Everything from start IGNORE_IN_PROMPT until end IGNORE_IN_PROMPT will be ignored here.
            if (strpos($line, '{start IGNORE_IN_PROMPT}') !== false) {
                $ignoreLine = true;
                continue;
            } else if (strpos($line, '{end IGNORE_IN_PROMPT}') !== false) {
                $ignoreLine = false;
                continue;
            }

            if ($ignoreLine) {
                continue;
            }

            /** @var Any $currentVariable */
            $currentVariable = $currentVariable ?: new Any('placeholder');

            if ($line[0] === '#') {
                $currentVariable->addAnnotation($line);
            } else {
                $matches = [];
                if (preg_match('/^([\w\s]+)=\s?({.*})?$/', $line, $matches) !== 1) {
                    // This variable already has a value, so we skip it
                    continue;
                }

                $variableName = $matches[1];

                $currentVariable->setName($variableName);
                $currentVariable->setBucketName(State::BUCKET_ENVVARS);

                $currentVariable->setKeyName(sprintf('%s.%s', $scope, $variableName));

                if (isset($matches[2])) {
                    $variableSettings = json_decode($matches[2]); // TODO: error handling

                    if (! $variableSettings) {
                        throw new \Exception(sprintf(
                            'These %s settings are not valid JSON:' . PHP_EOL . $matches[2],
                            $path
                        ));
                    }

                    $currentVariable->setType($variableSettings->type ?? null);

                    $currentVariable->setDefault($variableSettings->default ?? null);
                    $currentVariable->setExample($variableSettings->example ?? null);

                    $currentVariable->setShouldAskForInput($variableSettings->ask ?? true);
                }

                $currentVariable->setText(sprintf(
                    'Please enter a value for %s%s%s:',
                    $variableName,
                    ($currentVariable->example() ? sprintf(' (for example: %s)', $currentVariable->example()) : ''),
                    ($currentVariable->hasDefault() ? ' (or leave empty to use a default)' : '')
                ));

                $variables[$variableName] = $currentVariable; // Add this variable to the variable stack
                $currentVariable          = null; // Reset the current variable, since we're done with this one now
            }
        }

        return new Collection($variables);
    }
}