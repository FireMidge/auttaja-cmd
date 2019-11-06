<?php

namespace  AuttajaCmd\Input\InputType;

use AuttajaCmd\Input\State;

/**
 * Calculates the value of the "default" property in a .template file.
 */
class DefaultValueProcessor
{
    public function process(string $default, State $state, $defaultMethod = 'shell')
    {
        $matches = [];
        // TODO: Make more intelligent.. what if brackets are used inside the command?
        preg_match_all('#^(.*)(env|php|shell|string)\((.*)\)(.*$)#U', $default, $matches, PREG_SET_ORDER);

        $envVarsWithPrefix = $state->getValuesFromBucket(State::BUCKET_ENVVARS);

        if (empty ($matches)) {
            // Defaulting to shell command if no other known instruction (prefix) given
            return trim($this->calculateValue($defaultMethod, $default, $envVarsWithPrefix));
        }

        /**
         * @var string $fullMatch    e.g. "env(global.DATABASE_NAME)_test"
         * @var string $beforeMethod e.g. "" (everything before env())
         * @var string $method       e.g. "env"
         * @var string $command      e.g. "global.DATABASE_NAME"
         * @var string $afterMethod  e.g. "_test" (everything after env())
         */
        foreach ($matches as list($fullMatch, $beforeMethod, $method, $command, $afterMethod)) {
            return trim($beforeMethod . $this->calculateValue($method, $command, $envVarsWithPrefix) . $afterMethod);
        }

        return trim($default);
    }

    private function calculateValue(string $method, string $command, array $envVarsWithPrefix)
    {
        switch ($method) {
            case 'string':
                return $command;
                break;

            case 'env':
                if (array_key_exists($command, $envVarsWithPrefix)) {
                    return $envVarsWithPrefix[$command];
                }

                // If you want to use an env var from the .env file, you have to use "global.VAR_NAME".
                // This is to avoid ambiguity.
                throw new \Exception(sprintf(
                    'No environment variable found for "%s" - are you missing or misspelling a prefix? Allowed prefixes are: %s',
                    $command,
                    implode(', ', ['global', 'test']) // TODO: Allowed prefixes are dynamic
                ));
                break;

            case 'shell':
                return shell_exec($command);
                break;

            case 'php':
                return eval($command);
                break;

            default:
                throw new \Exception(sprintf(
                    'Unknown execution method "%s" for command "%s" in env template file', $method, $command
                ));
        }
    }
}