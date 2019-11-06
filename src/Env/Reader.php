<?php

namespace AuttajaCmd\Env;

use AuttajaCmd\Input\InputType\Any;
use AuttajaCmd\Input\InputType\Collection;
use AuttajaCmd\Input\State;

class Reader
{
    private $helper;

    public function __construct()
    {
        $this->helper = new Helper();
    }

    public function prepareInputs(array $filePaths, State $state, bool $forceReCreate) : Collection
    {
        $envVariables = new Collection();
        foreach ($filePaths as $filePath) {
            if (! $forceReCreate) {
                // If we are not recreating the file from scratch, read existing variables first.
                $this->saveVariablesFromEnvFileToState(
                    $filePath,
                    $state
                );
            }

            $envVariables = $envVariables->merge($this->getVariablesFromEnvTemplateFile($filePath, $state));
        }

//        return new Collection([
//            (new OneOfList('Would you like to set up the .env file now?', [
//                'y' => (new OneOfList\Option('Yes'))->setValue(true)->setIfSelected($setUpQuestions),
//                'n' => (new OneOfList\Option('No'))->setValue(false),
//            ]))->setShouldSave(false),
//        ]);

        return $envVariables;
    }

    private function saveVariablesFromEnvFileToState(string $templatePath, State $state) : void
    {
        $destinationPath = $this->helper->getDestinationFileFromTemplate($templatePath);

        if (! file_exists($destinationPath)) {
            return;
        }

        $fileHandle = fopen($destinationPath, 'r');
        $scope      = $this->helper->getEnvVarScopeFromTemplatePath($templatePath);

        while (($line = fgets($fileHandle)) !== false) {
            $line = trim($line);

            if (strlen($line) === 0) {
                // Empty line; skip.
                continue;
            }

            $matches = [];
            if (preg_match('/^([\w\s]+)=\s?(.+)$/', $line, $matches) === 1) {
                $varName = trim($matches[1]);
                $value   = trim($matches[2]);

                $state->withValue(sprintf('%s.%s', $scope, $varName), $value, State::BUCKET_ENVVARS);
            }
        }

        fclose($fileHandle);
    }

    private function getVariablesFromEnvTemplateFile(string $path, ?State $state =  null) : Collection
    {
        if (! file_exists($path)) {
            return new Collection();
        }

        $fileHandle = fopen($path, 'r');
        $scope      = $this->helper->getEnvVarScopeFromTemplatePath($path);

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
                    // This variable already has a hardcoded value, so we skip it
                    $currentVariable = null;

                    continue;
                }

                $variableName       = $matches[1];
                $scopedVariableName = sprintf('%s.%s', $scope, $variableName);

                if ($state instanceof State && ($state->getValue($scopedVariableName, State::BUCKET_ENVVARS) !== null)) {
                    // That variable already has a value saved against State, therefore we can skip it
                    $currentVariable = null;

                    continue;
                }

                $currentVariable->setName($variableName);
                $currentVariable->setBucketName(State::BUCKET_ENVVARS);

                $currentVariable->setKeyName($scopedVariableName);

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

                $variables[]     = $currentVariable; // Add this variable to the variable stack
                $currentVariable = null; // Reset the current variable, since we're done with this one now
            }
        }

        fclose($fileHandle);

        return new Collection($variables);
    }
}