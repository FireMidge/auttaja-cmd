<?php
declare(strict_types=1);

namespace AuttajaCmd\Input;

use function array_shift;
use function strlen;
use function sprintf;
use function array_map;
use function explode;
use function array_filter;
use function array_key_exists;
use function current;
use function count;
use function in_array;
use function array_merge;
use function is_numeric;
use function trim;

class CommandLineInput
{
    const TYPE_ANY           = 'any';
    const TYPE_BOOLEAN       = 'bool';
    const TYPE_INTEGER       = 'int';
    const TYPE_STRING        = 'string';
    const TYPE_MIXED_ARRAY   = 'array';
    const TYPE_STRING_ARRAY  = 'stringArray';
    const TYPE_INTEGER_ARRAY = 'intArray';

    const TYPES = [
        self::TYPE_BOOLEAN,
        self::TYPE_INTEGER,
        self::TYPE_STRING,
        self::TYPE_MIXED_ARRAY,
        self::TYPE_STRING_ARRAY,
        self::TYPE_INTEGER_ARRAY,
    ];

    const ARRAY_TO_SCALAR_TYPES = [
        self::TYPE_INTEGER_ARRAY => self::TYPE_INTEGER,
        self::TYPE_STRING_ARRAY  => self::TYPE_STRING,
        self::TYPE_MIXED_ARRAY   => self::TYPE_ANY,
    ];

    /** @var string The path to the command-line script. Will indicate from which directory the script was executed. */
    private $path;

    /** @var string|null Any command that was given before the first option or NULL for none. */
    private $command;

    /** @var string[][]  An array of arguments passed to the command line.
     *                   The key is the parameter with its - or -- prefix.
     *                   The value is [ true ] if no value was passed to the parameter,
     *                   or an array of values if at least one has been passed.
     *                   Comma-separated string parameters are converted to arrays.
     */
    private $parameters = [];

    public function __construct(array $commandLineArguments)
    {
        $this->path = array_shift($commandLineArguments);

        $parameterName = null;
        foreach ($commandLineArguments as $numericKey => $value) {
            if ($value[0] === '-') {
                // New parameter
                $parameterName = $this->initialiseParameter($value);

                continue;
            }

            if ($parameterName === null) {
                $this->command = $value;

                continue;
            }

            $value = array_filter(array_map('trim', explode(',', $value)), function($value) {
                return $value !== '';
            });

            $this->addValueToParameter($parameterName, $value);
        }
    }

    /**
     * Any command that was given before the first option or NULL for none.
     *
     * @return string|null
     */
    public function command() : ?string
    {
        return $this->command;
    }

    /**
     * Returns true if an argument was passed to the script, regardless of its value.
     *
     * @param string|null $longArgumentName   The argument' long version, e.g. "version", or NULL if there is none.
     * @param string@null $shortArgumentName  The argument's short version, e.g. "v", or NULL if there is none.
     *
     * @return bool
     */
    public function hasArgument(?string $longArgumentName, ?string $shortArgumentName) : bool
    {
        $paramFullNames = $this->getFullNamesForParameters($longArgumentName, $shortArgumentName);

        foreach ($paramFullNames as $fullName) {
            if (array_key_exists($fullName, $this->parameters)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns the value of the relevant argument.
     * Long argument names are prefixed by 2 dashes, short argument names by 1 dash, and they must not contain any white space.
     *
     * If an argument has been passed via the long AND the short name, the values of both are combined in an array.
     * Comma-separated values are returned as an array.
     * White-space-separated values are returned as an array, unless they were wrapped in quotes.
     * Boolean values may be provided as "false"/"true" or 0/1.
     *
     * Example 1:
     * Input: $ auttaja-cmd -b true --blo false yes there,hello
     * This method will return for option blo (with short form b) and type "TYPE_MIXED_ARRAY":
     * [ "false", "yes", "there", "hello", "true" ]
     *
     * Example 2:
     * Input: $ auttaja-cmd --bla="1,2,3,4,hello there, hola"
     * This method will return for option bla and type "TYPE_STRING_ARRAY":
     * [ "1", "2", "3", "4", "hello there", "hola" ]
     *
     * Example 3:
     * Input: $auttaja-cmd --blu
     * This option will return for option blu and type "TYPE_BOOLEAN":
     * true
     *
     * Example 3:
     * Input: $auttaja-cmd --blu=false
     * This option will return for option blu and type "TYPE_BOOLEAN":
     * false
     *
     * @param string|null $longArgumentName   The argument' long version, e.g. "version", or NULL if there is none.
     * @param string@null $shortArgumentName  The argument's short version, e.g. "v", or NULL if there is none.
     * @param string      $expectedType
     *
     * @return array|bool|int|string
     * @throws \Exception
     */
    public function parameterValue(?string $longArgumentName, ?string $shortArgumentName, string $expectedType)
    {
        // Not using mb_strlen here because it's less likely it will be installed locally
        $hasLongForm = strlen($longArgumentName) > 0;
        $parameterNamesString = sprintf(
            '%s%s',
            $hasLongForm ? $longArgumentName : '',
            $shortArgumentName === null ? '' :
                ($hasLongForm ? ' (' . $shortArgumentName . ')' : $shortArgumentName)
        );

        if (! in_array($expectedType, self::TYPES, true)) {
            throw new \Exception(
                'Unknown type "%s" for parameter "%s"',
                $expectedType,
                $parameterNamesString
            );
        }

        $paramFullNames = $this->getFullNamesForParameters($longArgumentName, $shortArgumentName);
        $combinedValue  = [];
        $paramExists    =  false;
        foreach ($paramFullNames as $fullName) {
            if (array_key_exists($fullName, $this->parameters)) {
                $paramExists   = true;
                $combinedValue = array_merge($combinedValue, $this->parameters[$fullName]);
            }
        }

        if (! in_array($expectedType, [self::TYPE_MIXED_ARRAY, self::TYPE_STRING_ARRAY, self::TYPE_INTEGER_ARRAY])) {
            if (count($combinedValue) > 1) {
                throw new \Exception(sprintf('Too many arguments supplied for option "%s"', $parameterNamesString));
            }

            if (empty($combinedValue)) {
                $value = $paramExists;
            } else {
                $value = current($combinedValue);
            }

            try {
                return $this->convertValueToType($value, $expectedType);
            } catch (\Exception $ex) {
                throw new \Exception(sprintf(
                    'Could not convert the value for parameter "%s" to "%s"',
                    $parameterNamesString,
                    $expectedType
                ), 0, $ex);
            }
        } else {
            $convertedValues = [];

            foreach ($combinedValue as $value) {
                try {
                    $expectedScalarType = self::ARRAY_TO_SCALAR_TYPES[$expectedType];
                    $convertedValues[]  = $this->convertValueToType($value, $expectedScalarType);
                } catch (\Exception $ex) {
                    throw new \Exception(sprintf(
                        'Could not convert value "%s" for parameter "%s" to "%s"',
                        (string) $value,
                        $parameterNamesString,
                        $expectedScalarType
                    ), 0, $ex);
                }
            }

            return $convertedValues;
        }
    }

    /**
     * Adds 2 dashes to the long argument name, and 1 dash to the short argument name,
     * and returns both of them if they both have been passed.
     *
     * @param string|null $longArgumentName   The long argument name WITHOUT PREFIX, e.g. "version".
     * @param string|null $shortArgumentName  The short argument name WITHOUT PREFIX, e.g. "v".
     *
     * @return string[]  All valid versions for the parameter, with the prefix attached.
     */
    private function getFullNamesForParameters(?string $longArgumentName, ?string $shortArgumentName) : array
    {
        $paramFullNames = [];
        if ($longArgumentName !== null) {
            $paramFullNames[] = '--' . $longArgumentName;
        }
        if ($shortArgumentName !== null) {
            $paramFullNames[] = '-' . $shortArgumentName;
        }

        return $paramFullNames;
    }

    /**
     * Converts $value to any supported scalar type.
     *
     * @param string|bool  $value  The value to convert. Typically a string, but may also be a boolean.
     * @param string       $type   The type to convert to. One of self::TYPE_*.
     *
     * @return bool|int|string
     * @throws \Exception  If a value could not be safely converted.
     */
    private function convertValueToType($value, string $type)
    {
        switch ($type) {
            case self::TYPE_BOOLEAN:
                // If value is NULL, then it is automatically assumed as TRUE, as just being present indicates TRUE
                if ($value === 'true' || $value === '1' || $value === true) {
                    return true;
                } else if ($value === 'false' || $value === '0' || $value === false) {
                    return false;
                } else {
                    throw new \Exception('Could not convert data type');
                }

            case self::TYPE_INTEGER:
                if (is_numeric($value)) {
                    return (int) $value;
                } else {
                    throw new \Exception('Could not convert data type');
                }

            case self::TYPE_STRING:
                return (string) $value;

            case self::TYPE_ANY:
                return $value;

            default:
                throw new \Exception(sprintf('Unrecognised data type "%s"', $type));
        }
    }

    /**
     * Records another value having been added to a parameter.
     *
     * @param string  $parameterName  The name of the parameter to which we are adding.
     * @param mixed   $value          The value to be added.
     */
    private function addValueToParameter(string $parameterName, $value) : void
    {
        if (! array_key_exists($parameterName, $this->parameters)) {
            $this->parameters[$parameterName] = [];
        } else if (! is_array($this->parameters[$parameterName])) {
            $this->parameters[$parameterName] = [
                // If it wasn't previously an array, convert it
                $this->parameters[$parameterName],
            ];
        }

        if (is_array($value)) {
            $this->parameters[$parameterName] = array_merge($this->parameters[$parameterName], $value);
        } else {
            $this->parameters[$parameterName][] = $value;
        }
    }

    /**
     * Records that an argument has been passed, and if it was passed with an equal sign, also
     * assigns the value passed along with it.
     * Returns the parameter name with its prefix.
     *
     * @param string $param  The whole parameter, e.g. --meep=1
     *
     * @return string
     */
    private function initialiseParameter(string $param) : string
    {
        $explodedByEqualSign = explode('=', $param);
        $usesEqualSign       = count($explodedByEqualSign) > 1;
        $paramName           = trim($explodedByEqualSign[0]);

        $value = []; // indicates the parameter is at least present
        if ($usesEqualSign) {
            $value = array_map('trim', explode(',', $explodedByEqualSign[1]));
        }

        $this->parameters[$paramName] = $value;

        return $paramName;
    }
}