<?php

namespace AuttajaCmd;

use AuttajaCmd\Env\Reader;
use AuttajaCmd\Input\InputProcessor;
use AuttajaCmd\Input\State;
use Env\Writer;

class Runner
{
    /**
     * @param string[] $filePaths The path(s) to the .env file(s) to create.
     *                            May be the path to the template or the destination file.
     */
    public function runEnvFileProcessor(array $filePaths = []) : void // TODO: Probably accept a string and make array conversion internally
    {
        $state = new State();

        $inputsToProcess = (new Reader())->prepareInputs($filePaths);

        $resultState = (new InputProcessor())->process($inputsToProcess, $state);

        var_dump($resultState);

        (new Writer())->writeFile($resultState, $filePaths);
    }
}