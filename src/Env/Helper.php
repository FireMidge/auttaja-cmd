<?php

namespace AuttajaCmd\Env;

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
        preg_match('#([\.\w]+)\.template#', $templatePath, $matches);

        if (empty($matches) || ! isset($matches[1])) {
            throw new \Exception(sprintf('Templates must end in .template; "%s" is invalid.',$templatePath));
        }

        return $matches[1];
    }
}