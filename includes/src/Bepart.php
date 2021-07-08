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
        return preg_replace('@^https?://@', '', trim($url));
    }

    private function rowh(string $name, string $pad = ' ', int $minlen = 15)
    {
        $len = $minlen - \strlen($name);
        if ($len < 0) {
            $len = $minlen;
        }

        return $name.str_repeat($pad, $len);
    }

    private function output($text, $is_error = false)
    {
        $fd = $is_error ? \STDERR : \STDOUT;
        fwrite($fd, $text);
    }

    private function output_debug($title, $pid, $msg)
    {
        if (isset($this->args['debug']) && $this->args['debug']) {
            $time = $this->rowh(sprintf('%.4F', microtime(true)), ' ', 16);
            $title = $this->rowh($title);
            $pid = $this->rowh($pid, ' ', 7);
            $this->output('# '.$time.': '.$title.': '.$pid.': '.$msg.\PHP_EOL);
        }
    }

    private function get_hash($string)
    {
        return substr(md5($string), 0, 12);
    }

    private function lockpath()
    {
        return $this->normalize_path(sys_get_temp_dir().'/');
    }

    private function wpdb()
    {
        if (isset($GLOBALS['wpdb']) && \is_object($GLOBALS['wpdb'])) {
            return $GLOBALS['wpdb'];
        }

        return false;
    }

    private function wpdb_reconnect()
    {
        $wpdb = $this->wpdb();
        if ($wpdb) {
            $wpdb->db_connect(false);
        }
    }

    private function wpdb_suppress_errors()
    {
        $wpdb = $this->wpdb();
        if ($wpdb) {
            $wpdb->suppress_errors(true);
        }
    }
}
