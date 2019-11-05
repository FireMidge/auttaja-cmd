<?php

namespace Env;

class Helper
{
    public function getEnvVarScopeFromPath(string $path) : string
    {
        $matches = [];
        preg_match('#\.env(\.(\w*))?\.template$#', $path, $matches);

        return(count($matches) === 3)
            ? $matches[2]
            : 'global';
    }

    public function getDestinationFileFromTemplate(string $templatePath) : string
    {
        return sscanf('%s.template', $templatePath);
    }
}