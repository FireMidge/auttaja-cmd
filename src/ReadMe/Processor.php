<?php
declare(strict_types=1);

namespace AuttajaCmd\ReadMe;

use function file_exists;
use function file_put_contents;
use function preg_match;
use function sprintf;
use function array_key_exists;
use function preg_match_all;
use function is_dir;
use function strpos;
use function mkdir;
use function realpath;
use function str_replace;

class Processor
{
    public function write(array $filePaths, array $envVars) : void
    {
        foreach ($filePaths as $fileOrDirectoryPath => $destinationFileOrDirectoryPath) {
            $fullDestinationPath = realpath($destinationFileOrDirectoryPath);
            if ($fullDestinationPath === false) {
                throw new \Exception(sprintf('Destination path %s does not exist', $fullDestinationPath));
            }

            if (strpos(__DIR__, $fullDestinationPath) !== 0) {
                throw new \Exception(sprintf(
                    'Destination path has to be in the current application\'s directory, "%s" is invalid',
                    $fullDestinationPath
                ));
            }

            if (is_dir($fileOrDirectoryPath)) {
                $this->processDirectory($fileOrDirectoryPath, $destinationFileOrDirectoryPath, $envVars);
            } else {
                $this->processFile($fileOrDirectoryPath, $destinationFileOrDirectoryPath, $envVars);
            }
        }
    }

    private function processDirectory(string $directoryPath, string $destinationPath, array $envVars) : void
    {
        $filesInDirectory = scandir($directoryPath);

        if (! file_exists($destinationPath)) {
            mkdir($destinationPath);
        }

        foreach ($filesInDirectory as $file) {
            if ($file === '..' || $file === '.') {
                continue;
            }

            $filePath            = $directoryPath . DIRECTORY_SEPARATOR . $file;
            $destinationFilePath = $destinationPath . DIRECTORY_SEPARATOR . $file;

            if (is_dir($filePath)) {
                $this->processDirectory($filePath, $destinationFilePath, $envVars);
            } else {
                $this->processFile($filePath, $destinationFilePath, $envVars);
            }
        }
    }

    private function processFile(string $filePath, string $destinationPath, array $envVars) : void
    {
        try {
            $destinationPath = $this->getDestinationFileFromTemplate($destinationPath);
        } catch (\Exception $ex) {
            // Not a template file - ignore.
            return;
        }

        $content = file_get_contents($filePath);

        $matches = [];
        preg_match_all('/{#([\w_\-\.]+)#}/', $content, $matches);

        if (! isset($matches[1])) {
            // Nothing found to replace
            file_put_contents($destinationPath, $content);

            echo '(Re)Created ' . $destinationPath . PHP_EOL;

            return;
        }

        foreach ($matches[1] as $match) {
            if (! array_key_exists($match, $envVars)) {
                // Don't replace - we don't have a value for it.
                continue;
            }

            $content = str_replace('{#' . $match . '#}', $envVars[$match], $content);
        }

        file_put_contents($destinationPath, $content);

        echo '(Re)Created ' . $destinationPath . PHP_EOL;
    }

    /**
     * Copy & paste from Env/Helper.
     */
    private function getDestinationFileFromTemplate(string $templatePath) : string
    {
        $matches = [];
        preg_match('#(.+)\.template#', $templatePath, $matches);

        if (empty($matches) || ! isset($matches[1])) {
            throw new \Exception(sprintf('Templates must end in .template; "%s" is invalid.',$templatePath));
        }

        return $matches[1];
    }
}