<?php

/**
 * Docket CronWP.
 *
 * @author  Nawawi Jamili
 * @license MIT
 *
 * @see    https://github.com/nawawi/docket-cronwp
 */

/**
 * Credits:
 *  https://github.com/phalcon/cli-options-parser/blob/master/src/Parser.php.
 */

namespace Nawawi\DocketCronWP;

\defined('DOCKET_CRONWP') || exit;

class Parser
{
    private $boolParamSet = [
        'y' => true,
        'n' => false,
        'yes' => true,
        'no' => false,
        'true' => true,
        'false' => false,
        '1' => true,
        '0' => false,
        'on' => true,
        'off' => false,
    ];

    private $parsedCommands = [];

    public function has($key)
    {
        return isset($this->parsedCommands[$key]);
    }

    public function get($key, $default = null)
    {
        if (!$this->has($key)) {
            return $default;
        }

        return $this->parsedCommands[$key];
    }

    public function getBoolean(string $key, bool $default = false)
    {
        if (!$this->has($key)) {
            return $default;
        }

        if (\is_bool($this->parsedCommands[$key]) || \is_int($this->parsedCommands[$key])) {
            return (bool) $this->parsedCommands[$key];
        }

        return $this->getCoalescingDefault($this->parsedCommands[$key], $default);
    }

    public function parse(array $argv = [])
    {
        if (empty($argv)) {
            $argv = $this->getArgvFromServer();
        }

        array_shift($argv);
        $this->parsedCommands = [];

        return $this->handleArguments($argv);
    }

    private function getArgvFromServer()
    {
        return empty($_SERVER['argv']) ? [] : $_SERVER['argv'];
    }

    private function getCoalescingDefault(string $value, bool $default)
    {
        return $this->boolParamSet[$value] ?? $default;
    }

    private function getParamWithEqual(string $arg, int $eqPos)
    {
        $key = $this->stripSlashes(substr($arg, 0, $eqPos));
        $out[$key] = substr($arg, $eqPos + 1);

        return $out;
    }

    private function handleArguments(array $argv)
    {
        for ($i = 0, $j = \count($argv); $i < $j; ++$i) {
            // --foo --bar=baz
            if ('--' === substr($argv[$i], 0, 2)) {
                if ($this->parseAndMergeCommandWithEqualSign($argv[$i])) {// --bar=baz
                    continue;
                }

                $key = $this->stripSlashes($argv[$i]);
                if ($i + 1 < $j && '-' !== $argv[$i + 1][0]) {// --foo value
                    $this->parsedCommands[$key] = $argv[$i + 1];
                    ++$i;
                    continue;
                }
                $this->parsedCommands[$key] = $this->parsedCommands[$key] ?? true;
                continue;
            }

            // -k=value -abc
            if ('-' === substr($argv[$i], 0, 1)) {
                if ($this->parseAndMergeCommandWithEqualSign($argv[$i])) {
                    continue;
                }

                // -a value1 -abc value2 -abc
                $hasNextElementDash = $i + 1 < $j && '-' !== $argv[$i + 1][0] ? false : true;
                foreach (str_split(substr($argv[$i], 1)) as $char) {
                    $this->parsedCommands[$char] = $hasNextElementDash ? true : $argv[$i + 1];
                }

                if (!$hasNextElementDash) {// -a value1 -abc value2
                    ++$i;
                }
                continue;
            }

            $this->parsedCommands[] = $argv[$i];
        }

        return $this->parsedCommands;
    }

    private function parseAndMergeCommandWithEqualSign(string $command)
    {
        $eqPos = strpos($command, '=');

        if (false !== $eqPos) {
            $this->parsedCommands = array_merge($this->parsedCommands, $this->getParamWithEqual($command, $eqPos));

            return true;
        }

        return false;
    }

    private function stripSlashes(string $argument)
    {
        if ('-' !== substr($argument, 0, 1)) {
            return $argument;
        }

        $argument = substr($argument, 1);

        return $this->stripSlashes($argument);
    }

    public function getParsedCommands()
    {
        return $this->parsedCommands;
    }
}
