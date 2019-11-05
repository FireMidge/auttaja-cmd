<?php

namespace Env;

use AuttajaCmd\Input\State;

class Writer
{
    private $helper;

    public function __construct()
    {
        $this->helper = new Helper();
    }

    public function writeFile(State $state, array $filePaths) : void
    {
        foreach ($filePaths as $pathToTemplateFile) {
            $this->createEnvFile(
                $pathToTemplateFile,
                $this->helper->getDestinationFileFromTemplate($pathToTemplateFile),
                $state->getValuesFromBucket(State::BUCKET_ENVVARS),
                $this->helper->getEnvVarScopeFromPath($pathToTemplateFile)
            );
        }
    }

    private function createEnvFile(string $pathToTemplateFile, string $destinationFile, array $environmentVariables, string $scope) : void
    {
        if (! file_exists($pathToTemplateFile)) {
            throw new \Exception(sprintf(
                'There is no %s file to base the new %s file on',
                $pathToTemplateFile,
                $destinationFile
            ));
        }

        $envFileLines = [];

        $fileHandle = fopen($pathToTemplateFile, 'r');
        while (($line = fgets($fileHandle)) !== false) {
            $line = trim($line);

            if (strlen($line) === 0) {
                // Retain empty lines
                $envFileLines[] = '';

                continue;
            }

            if ($line[0] === '#') {
                // Don't copy over comments
                continue;
            }

            $matches = [];
            if (preg_match('/^([\w\s]+)=(.*)$/', $line, $matches)) {
                $varName = $matches[1];
                $scopedVarName = sprintf('%s.%s', $scope, $varName);

                if (array_key_exists($scopedVarName, $environmentVariables)) {
                    // If the value has been provided for this environment variable (either by user input or by evaluating the default), use it
                    $envFileLines[] = sprintf('%s=%s', $varName, $environmentVariables[$scopedVarName]);

                } else if (isset($matches[2]) && $matches[2][0] !== '{') {
                    // If the value for the variable is hardcoded, use that
                    $envFileLines[] = sprintf('%s=%s', $varName, $matches[2]);

                } else {
                    throw new \Exception(sprintf(
                        'Unable to determine value for environment variable "%s". '
                        . 'Perhaps no value was provided and no default specified. '
                        . PHP_EOL . 'These are the values I have: %s',
                        $varName,
                        PHP_EOL . implode(PHP_EOL, $environmentVariables) . PHP_EOL
                    ));
                }
            }
        }

        file_put_contents($destinationFile, implode(PHP_EOL, $envFileLines));
    }
}