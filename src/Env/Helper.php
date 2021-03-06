<?php
declare(strict_types=1);

namespace AuttajaCmd\Env;

use function preg_match;
use function count;
use function sprintf;

class Helper
{
    public function getEnvVarScopeFromTemplatePath(string $path) : string
    {
        $matches = [];
        preg_match('#\.env(\.(\w*))?\.template$#', $path, $matches);

        return(count($matches) === 3)
            ? $matches[2]
            : 'global';
    }

    public function getDestinationFileFromTemplate(string $templatePath) : string
    {
        $matches = [];
        preg_match('#(.+)\.template#', $templatePath, $matches);

        if (empty($matches) || ! isset($matches[1])) {
            throw new \Exception(sprintf('Templates must end in .template; "%s" is invalid.',$templatePath));
        }

        return $matches[1];
    }
}