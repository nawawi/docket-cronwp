<?php
/**
 * Docket CronWP.
 *
 * @author  Nawawi Jamili
 * @license MIT
 *
 * @see    https://github.com/nawawi/docket-cronwp
 */

namespace Nawawi\DocketCronWP;

\defined('DOCKET_CRONWP') || exit;

/**
 * Class Parser
 * https://github.com/phalcon/cli-options-parser/blob/master/src/Parser.php.
 *
 * New BSD License
 *
 * Copyright (c) 2011-present, Phalcon Team
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *    * Redistributions of source code must retain the above copyright
 *      notice, this list of conditions and the following disclaimer.
 *    * Redistributions in binary form must reproduce the above copyright
 *      notice, this list of conditions and the following disclaimer in the
 *      documentation and/or other materials provided with the distribution.
 *    * Neither the name of the Phalcon nor the
 *      names of its contributors may be used to endorse or promote products
 *      derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL PHALCON TEAM BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */
trait Parser
{
    private $bool_param = [
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

    private $parsed_cmds = [];

    public function has($key)
    {
        return isset($this->parsed_cmds[$key]);
    }

    public function get($key, $default = null)
    {
        if (!$this->has($key)) {
            return $default;
        }

        return $this->parsed_cmds[$key];
    }

    public function get_bool(string $key, bool $default = false)
    {
        if (!$this->has($key)) {
            return $default;
        }

        if (\is_bool($this->parsed_cmds[$key]) || \is_int($this->parsed_cmds[$key])) {
            return (bool) $this->parsed_cmds[$key];
        }

        return $this->get_all_default($this->parsed_cmds[$key], $default);
    }

    public function parse(array $argv = [])
    {
        if (empty($argv)) {
            $argv = $this->get_argv_server();
        }

        array_shift($argv);
        $this->parsed_cmds = [];

        return $this->handle_argv($argv);
    }

    private function get_argv_server()
    {
        return empty($_SERVER['argv']) ? [] : $_SERVER['argv'];
    }

    private function get_all_default(string $value, bool $default)
    {
        return $this->bool_param[$value] ?? $default;
    }

    private function get_param_equal(string $arg, int $offset)
    {
        $key = $this->strip_slashes(substr($arg, 0, $offset));
        $out[$key] = substr($arg, $offset + 1);

        return $out;
    }

    private function handle_argv(array $argv)
    {
        for ($i = 0, $j = \count($argv); $i < $j; ++$i) {
            if ('--' === substr($argv[$i], 0, 2)) {
                if ($this->parse_merge_cmds_equalsign($argv[$i])) {
                    continue;
                }

                $key = $this->strip_slashes($argv[$i]);
                if ($i + 1 < $j && '-' !== $argv[$i + 1][0]) {
                    $this->parsed_cmds[$key] = $argv[$i + 1];
                    ++$i;
                    continue;
                }
                $this->parsed_cmds[$key] = $this->parsed_cmds[$key] ?? true;
                continue;
            }

            if ('-' === substr($argv[$i], 0, 1)) {
                if ($this->parse_merge_cmds_equalsign($argv[$i])) {
                    continue;
                }

                $next_dash = $i + 1 < $j && '-' !== $argv[$i + 1][0] ? false : true;
                foreach (str_split(substr($argv[$i], 1)) as $char) {
                    $this->parsed_cmds[$char] = $next_dash ? true : $argv[$i + 1];
                }

                if (!$next_dash) {
                    ++$i;
                }
                continue;
            }

            $this->parsed_cmds[] = $argv[$i];
        }

        return $this->parsed_cmds;
    }

    private function parse_merge_cmds_equalsign(string $cmd)
    {
        $offset = strpos($cmd, '=');

        if (false !== $offset) {
            $this->parsed_cmds = array_merge($this->parsed_cmds, $this->get_param_equal($cmd, $offset));

            return true;
        }

        return false;
    }

    private function strip_slashes(string $arg)
    {
        if ('-' !== substr($arg, 0, 1)) {
            return $arg;
        }

        $arg = substr($arg, 1);

        return $this->strip_slashes($arg);
    }

    public function getparsed_cmds()
    {
        return $this->parsed_cmds;
    }
}
