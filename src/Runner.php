<?php
declare(strict_types=1);

namespace AuttajaCmd;

use AuttajaCmd\Env\Reader;
use AuttajaCmd\Input\InputProcessor;
use AuttajaCmd\Input\State;
use AuttajaCmd\Env\Writer;
use AuttajaCmd\ReadMe\Processor;

class Runner
{
    /**
     * @param string[] $filePaths The path(s) to the .env file(s) to create.
     *                            May be the path to the template or the destination file.
     */
    public function runEnvFileProcessor(array $filePaths, array $readMeFilePaths, bool $forceReCreate = false) : void
    {
        if (empty($filePaths)) {
            $filePaths = [
                '.env.template',
                '.env.test.template',
            ];
        }

        $state = new State();

        $inputsToProcess = (new Reader())->prepareInputs($filePaths, $state, $forceReCreate);

        $resultState = (new InputProcessor())->process($inputsToProcess, $state);

        (new Writer())->writeFile($resultState, $filePaths);

        $this->runReadMeFileProcessor($readMeFilePaths, $filePaths, $resultState);
    }

    public function runReadMeFileProcessor(array $readMeFilePaths, array $envFilePaths, ?State $state = null) : void
    {
        if (! $state) {
            $state = new State();

            foreach ($envFilePaths as $envFilePath) {
                (new Reader())->saveVariablesFromEnvFileToState($envFilePath, $state);
            }
        }

        (new Processor())->write($readMeFilePaths, $state->getValuesFromBucket(State::BUCKET_ENVVARS));
    }
}