<?php

namespace AuttajaCmd;

use AuttajaCmd\Env\Reader;
use AuttajaCmd\Input\InputProcessor;
use AuttajaCmd\Input\State;
use AuttajaCmd\Env\Writer;

class Runner
{
    /**
     * @param string[] $filePaths The path(s) to the .env file(s) to create.
     *                            May be the path to the template or the destination file.
     */
    public function runEnvFileProcessor(array $filePaths = [], bool $forceReCreate = false) : void
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
    }
}