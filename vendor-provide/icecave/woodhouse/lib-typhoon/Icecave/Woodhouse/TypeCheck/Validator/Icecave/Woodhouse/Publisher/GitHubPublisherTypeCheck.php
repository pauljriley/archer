<?php
namespace Icecave\Woodhouse\TypeCheck\Validator\Icecave\Woodhouse\Publisher;

class GitHubPublisherTypeCheck extends \Icecave\Woodhouse\TypeCheck\AbstractValidator
{
    public function validateConstruct(array $arguments)
    {
        $argumentCount = \count($arguments);
        if ($argumentCount > 1) {
            throw new \Icecave\Woodhouse\TypeCheck\Exception\UnexpectedArgumentException(1, $arguments[1]);
        }
    }

    public function publish(array $arguments)
    {
        if (\count($arguments) > 0) {
            throw new \Icecave\Woodhouse\TypeCheck\Exception\UnexpectedArgumentException(0, $arguments[0]);
        }
    }

    public function doPublish(array $arguments)
    {
        $argumentCount = \count($arguments);
        if ($argumentCount < 1) {
            throw new \Icecave\Woodhouse\TypeCheck\Exception\MissingArgumentException('tempDir', 0, 'string');
        } elseif ($argumentCount > 1) {
            throw new \Icecave\Woodhouse\TypeCheck\Exception\UnexpectedArgumentException(1, $arguments[1]);
        }
        $value = $arguments[0];
        if (!\is_string($value)) {
            throw new \Icecave\Woodhouse\TypeCheck\Exception\UnexpectedArgumentValueException(
                'tempDir',
                0,
                $arguments[0],
                'string'
            );
        }
    }

    public function repository(array $arguments)
    {
        if (\count($arguments) > 0) {
            throw new \Icecave\Woodhouse\TypeCheck\Exception\UnexpectedArgumentException(0, $arguments[0]);
        }
    }

    public function setRepository(array $arguments)
    {
        $argumentCount = \count($arguments);
        if ($argumentCount < 1) {
            throw new \Icecave\Woodhouse\TypeCheck\Exception\MissingArgumentException('repository', 0, 'string');
        } elseif ($argumentCount > 1) {
            throw new \Icecave\Woodhouse\TypeCheck\Exception\UnexpectedArgumentException(1, $arguments[1]);
        }
        $value = $arguments[0];
        if (!\is_string($value)) {
            throw new \Icecave\Woodhouse\TypeCheck\Exception\UnexpectedArgumentValueException(
                'repository',
                0,
                $arguments[0],
                'string'
            );
        }
    }

    public function repositoryUrl(array $arguments)
    {
        if (\count($arguments) > 0) {
            throw new \Icecave\Woodhouse\TypeCheck\Exception\UnexpectedArgumentException(0, $arguments[0]);
        }
    }

    public function branch(array $arguments)
    {
        if (\count($arguments) > 0) {
            throw new \Icecave\Woodhouse\TypeCheck\Exception\UnexpectedArgumentException(0, $arguments[0]);
        }
    }

    public function setBranch(array $arguments)
    {
        $argumentCount = \count($arguments);
        if ($argumentCount < 1) {
            throw new \Icecave\Woodhouse\TypeCheck\Exception\MissingArgumentException('branch', 0, 'string');
        } elseif ($argumentCount > 1) {
            throw new \Icecave\Woodhouse\TypeCheck\Exception\UnexpectedArgumentException(1, $arguments[1]);
        }
        $value = $arguments[0];
        if (!\is_string($value)) {
            throw new \Icecave\Woodhouse\TypeCheck\Exception\UnexpectedArgumentValueException(
                'branch',
                0,
                $arguments[0],
                'string'
            );
        }
    }

    public function commitMessage(array $arguments)
    {
        if (\count($arguments) > 0) {
            throw new \Icecave\Woodhouse\TypeCheck\Exception\UnexpectedArgumentException(0, $arguments[0]);
        }
    }

    public function setCommitMessage(array $arguments)
    {
        $argumentCount = \count($arguments);
        if ($argumentCount < 1) {
            throw new \Icecave\Woodhouse\TypeCheck\Exception\MissingArgumentException('commitMessage', 0, 'string');
        } elseif ($argumentCount > 1) {
            throw new \Icecave\Woodhouse\TypeCheck\Exception\UnexpectedArgumentException(1, $arguments[1]);
        }
        $value = $arguments[0];
        if (!\is_string($value)) {
            throw new \Icecave\Woodhouse\TypeCheck\Exception\UnexpectedArgumentValueException(
                'commitMessage',
                0,
                $arguments[0],
                'string'
            );
        }
    }

    public function authToken(array $arguments)
    {
        if (\count($arguments) > 0) {
            throw new \Icecave\Woodhouse\TypeCheck\Exception\UnexpectedArgumentException(0, $arguments[0]);
        }
    }

    public function setAuthToken(array $arguments)
    {
        $argumentCount = \count($arguments);
        if ($argumentCount < 1) {
            throw new \Icecave\Woodhouse\TypeCheck\Exception\MissingArgumentException('authToken', 0, 'string|null');
        } elseif ($argumentCount > 1) {
            throw new \Icecave\Woodhouse\TypeCheck\Exception\UnexpectedArgumentException(1, $arguments[1]);
        }
        $value = $arguments[0];
        if (!(\is_string($value) || $value === null)) {
            throw new \Icecave\Woodhouse\TypeCheck\Exception\UnexpectedArgumentValueException(
                'authToken',
                0,
                $arguments[0],
                'string|null'
            );
        }
    }

    public function execute(array $arguments)
    {
        $argumentCount = \count($arguments);
        if ($argumentCount < 1) {
            throw new \Icecave\Woodhouse\TypeCheck\Exception\MissingArgumentException('command', 0, 'string');
        }
        $value = $arguments[0];
        if (!\is_string($value)) {
            throw new \Icecave\Woodhouse\TypeCheck\Exception\UnexpectedArgumentValueException(
                'command',
                0,
                $arguments[0],
                'string'
            );
        }
        if ($argumentCount > 1) {
            $check = function ($argument, $index) {
                $value = $argument;
                $check = function ($value) {
                    if (\is_string($value) || \is_int($value) || \is_float($value)) {
                        return true;
                    }
                    if (!\is_object($value)) {
                        return false;
                    }
                    $reflector = new \ReflectionObject($value);
                    return $reflector->hasMethod('__toString');
                };
                if (!$check($argument)) {
                    throw new \Icecave\Woodhouse\TypeCheck\Exception\UnexpectedArgumentValueException(
                        'argument',
                        $index,
                        $argument,
                        'stringable'
                    );
                }
            };
            for ($index = 1; $index < $argumentCount; $index++) {
                $check($arguments[$index], $index);
            }
        }
    }

    public function tryExecute(array $arguments)
    {
        $argumentCount = \count($arguments);
        if ($argumentCount < 1) {
            throw new \Icecave\Woodhouse\TypeCheck\Exception\MissingArgumentException('command', 0, 'string');
        }
        $value = $arguments[0];
        if (!\is_string($value)) {
            throw new \Icecave\Woodhouse\TypeCheck\Exception\UnexpectedArgumentValueException(
                'command',
                0,
                $arguments[0],
                'string'
            );
        }
        if ($argumentCount > 1) {
            $check = function ($argument, $index) {
                $value = $argument;
                $check = function ($value) {
                    if (\is_string($value) || \is_int($value) || \is_float($value)) {
                        return true;
                    }
                    if (!\is_object($value)) {
                        return false;
                    }
                    $reflector = new \ReflectionObject($value);
                    return $reflector->hasMethod('__toString');
                };
                if (!$check($argument)) {
                    throw new \Icecave\Woodhouse\TypeCheck\Exception\UnexpectedArgumentValueException(
                        'argument',
                        $index,
                        $argument,
                        'stringable'
                    );
                }
            };
            for ($index = 1; $index < $argumentCount; $index++) {
                $check($arguments[$index], $index);
            }
        }
    }

    public function tryExecuteArray(array $arguments)
    {
        $argumentCount = \count($arguments);
        if ($argumentCount < 2) {
            if ($argumentCount < 1) {
                throw new \Icecave\Woodhouse\TypeCheck\Exception\MissingArgumentException('command', 0, 'string');
            }
            throw new \Icecave\Woodhouse\TypeCheck\Exception\MissingArgumentException('arguments', 1, 'array<stringable>');
        } elseif ($argumentCount > 2) {
            throw new \Icecave\Woodhouse\TypeCheck\Exception\UnexpectedArgumentException(2, $arguments[2]);
        }
        $value = $arguments[0];
        if (!\is_string($value)) {
            throw new \Icecave\Woodhouse\TypeCheck\Exception\UnexpectedArgumentValueException(
                'command',
                0,
                $arguments[0],
                'string'
            );
        }
        $value = $arguments[1];
        $check = function ($value) {
            if (!\is_array($value)) {
                return false;
            }
            $valueCheck = function ($subValue) {
                if (\is_string($subValue) || \is_int($subValue) || \is_float($subValue)) {
                    return true;
                }
                if (!\is_object($subValue)) {
                    return false;
                }
                $reflector = new \ReflectionObject($subValue);
                return $reflector->hasMethod('__toString');
            };
            foreach ($value as $key => $subValue) {
                if (!$valueCheck($subValue)) {
                    return false;
                }
            }
            return true;
        };
        if (!$check($arguments[1])) {
            throw new \Icecave\Woodhouse\TypeCheck\Exception\UnexpectedArgumentValueException(
                'arguments',
                1,
                $arguments[1],
                'array<stringable>'
            );
        }
    }

}
