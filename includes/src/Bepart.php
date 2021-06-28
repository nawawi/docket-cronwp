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

trait Bepart
{
    private function normalize_path($path)
    {
        $wrapper = '';
        $scheme_separator = strpos($path, '://');
        if (false !== $scheme_separator) {
            $stream = substr($path, 0, $scheme_separator);
            if (\in_array($stream, stream_get_wrappers(), true)) {
                list($wrapper, $path) = explode('://', $path, 2);
                $wrapper .= '://';
            }
        }

        $path = str_replace('\\', '/', $path);
        $path = preg_replace('|(?<=.)/+|', '/', $path);
        if (':' === substr($path, 1, 1)) {
            $path = ucfirst($path);
        }

        return $wrapper.$path;
    }

    private function strip_proto($url)
    {
        return preg_replace('@^https?://@', '', $url);
    }

    private function result_export($data)
    {
        $data_e = var_export($data, true);
        $data_e = preg_replace('/^([ ]*)(.*)/m', '$1$1$2', $data_e);
        $data_r = preg_split("/\r\n|\n|\r/", $data_e);
        $data_r = preg_replace(['/\s*array\s\($/', '/\)(,)?$/', '/\s=>\s$/'], [null, ']$1', ' => ['], $data_r);
        $data = implode(\PHP_EOL, array_filter(['['] + $data_r));

        return str_replace([',', "'"], '', $data);
    }

    private function output($text, $is_error = false)
    {
        $fd = $is_error ? \STDERR : \STDOUT;
        fwrite($fd, $text);
    }

    private function get_hash($string)
    {
        return substr(md5($string), 0, 12);
    }

    private function lockpath()
    {
        return $this->normalize_path(sys_get_temp_dir().'/');
    }
}
