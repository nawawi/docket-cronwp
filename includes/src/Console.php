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

final class Console extends Parser
{
    private $pids = [];
    private $key;
    private $args = [
        'dcdir' => '',
        'wpdir' => '',
        'job' => 5,
        'quiet' => false,
        'dryrun' => false,
        'network' => false,
        'runnow' => false,
        'site' => '',
        'help' => false,
        'version' => false,
    ];

    public function __construct()
    {
        pcntl_async_signals(true);
        $this->compat_notice();
        $this->register_args();
        $this->register_wpload();
    }

    private function compat_notice()
    {
        if (!(\PHP_VERSION_ID >= 70205)) {
            $this->output('Docket CronWP requires PHP 7.2.5 or greater.'.\PHP_EOL);
            exit(2);
        }

        if (!\extension_loaded('pcntl')) {
            $this->output('Docket CronWP requires pcntl extension.'.\PHP_EOL);
            exit(2);
        }
    }

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

    private function output($text, $is_error = false)
    {
        $fd = $is_error ? \STDERR : \STDOUT;
        fwrite($fd, $text);
    }

    private function register_wpdir($params)
    {
        $wpdir = '';
        if (empty($params)) {
            $wpdir = realpath(getcwd());
        } elseif (!empty($params[0])) {
            $wpdir = realpath($params[0]);
        } elseif (!empty($params['path'])) {
            $wpdir = realpath($params['path']);
        } elseif (!empty($params['p'])) {
            $wpdir = realpath($params['p']);
        }

        if (!empty($wpdir) && is_dir($wpdir)) {
            $this->args['wpdir'] = $this->normalize_path($wpdir);
        }
    }

    private function app()
    {
        return (object) [
             'name' => basename(DOCKET_CRONWP),
             'version' => DOCKET_CRONWP_VERSION,
             'path' => DOCKET_CRONWP_DIR,
         ];
    }

    private function print_banner()
    {
        $this->output(\PHP_EOL.'Docket CronWP v'.$this->app()->version.\PHP_EOL.'Execute WordPress cron events in parallel.'.\PHP_EOL.\PHP_EOL);
    }

    private function print_usage()
    {
        $text = '';
        $text .= 'Usage:'.\PHP_EOL;
        $text .= '  '.$this->app()->name.' [<path>|<options>]'.\PHP_EOL.\PHP_EOL;
        $text .= 'Path:'.\PHP_EOL;
        $text .= '  Path to the WordPress files.'.\PHP_EOL.\PHP_EOL;
        $text .= 'Options:'.\PHP_EOL;
        $text .= '  -p --path <path>      Path to the WordPress files.'.\PHP_EOL;
        $text .= '  -j --jobs <number>    Run number of events in parallel.'.\PHP_EOL;
        $text .= '  -a --run-now          Run all cron event.'.\PHP_EOL;
        $text .= '  -t --dry-run          Run without execute cron event.'.\PHP_EOL;
        $text .= '  -h --help             Display this help message.'.\PHP_EOL;
        $text .= '  -q --quiet            Suppress informational messages.'.\PHP_EOL;
        $text .= '  -V --version          Display version.'.\PHP_EOL;
        $this->output($text);
    }

    private function print_args()
    {
        $text = 'Path: '.$this->args['wpdir'].\PHP_EOL;
        $this->output($text);
    }

    private function register_args()
    {
        $this->args['dcdir'] = $this->normalize_path($this->app()->path);
        $params = $this->parse();
        $this->register_wpdir($params);

        foreach ($params as $key => $value) {
            switch ($key) {
                case 't':
                case 'dry-run':
                    $this->args['dryrun'] = $this->getBoolean($key, false);
                    break;
                case 'q':
                case 'quiet':
                    $this->args['quiet'] = $this->getBoolean($key, false);
                    break;
                case 'a':
                case 'run-now':
                    $this->args['runnow'] = $this->getBoolean($key, false);
                    break;
                case 'h':
                case 'help':
                    $this->args['help'] = $this->getBoolean($key, false);
                    break;
                case 'V':
                case 'version':
                    $this->args['version'] = $this->getBoolean($key, false);
                    break;
                case 'j':
                case 'jobs':
                    $job = (int) $value;
                    $this->args['job'] = $job < 0 ? 1 : $job;
                    break;
                case 'n':
                case 'network':
                    $this->args['network'] = $this->getBoolean($key, false);
                    break;
                case 's':
                case 'site':
                    $this->args['site'] = $value;
                    break;
            }
        }

        if ($this->args['version']) {
            $this->output('Docket CronWP v'.$this->app()->version.\PHP_EOL);
            exit(0);
        }

        if ($this->args['help']) {
            $this->print_banner();
            $this->print_usage();
            exit(0);
        }

        if (empty($this->args['wpdir']) || !is_file($this->args['wpdir'].'/wp-load.php')) {
            if (is_file($this->args['dcdir'].'/../wp-load.php')) {
                $this->args['wpdir'] = $this->normalize_path(realpath($this->args['dcdir'].'/../'));
            }
        }

        if (empty($this->args['wpdir']) || !is_file($this->args['wpdir'].'/wp-load.php')) {
            $app = $this->app()->name;
            $this->output('No WordPress installation found, run '.$app.' `path/to/wordpress`.'.\PHP_EOL.'Run '.$app.' --help for more options.'.\PHP_EOL, true);
            exit(1);
        }

        if (!$this->args['quiet']) {
            $this->print_args();
        }
    }

    private function strip_proto($url)
    {
        return preg_replace('@^https?://@', '', $url);
    }

    private function proc_store($key, $hook, $data)
    {
        $file = sys_get_temp_dir().'/'.$this->key.'-'.substr(md5($hook), 0, 12).'.php';

        if (empty($data) || !\is_array($data)) {
            return false;
        }

        $code = '<?php return '.var_export($data, 1).';';

        if (file_put_contents($file, $code, \LOCK_EX)) {
            chmod($file, 0666);

            return true;
        }

        return false;
    }

    private function proc_get($key, $hook)
    {
        $file = sys_get_temp_dir().'/'.$this->key.'-'.substr(md5($hook), 0, 12).'.php';
        if (empty($file) || !is_file($file)) {
            return false;
        }

        $data = include $file;
        unlink($file);
        if (!empty($data) && \is_array($data)) {
            return $data;
        }

        return false;
    }

    private function proc_fork($name, $callback)
    {
        if (!\is_callable($callback)) {
            $this->output($callback.' is not callable'.\PHP_EOL, true);

            return false;
        }

        $pid = pcntl_fork();
        if (-1 === $pid) {
            $this->output('Failed to fork the cron event '.$name.\PHP_EOL);

            return false;
        }
        if ($pid) {
            $this->pids[$name] = $pid;

            return true;
        }
        \call_user_func($callback);
        exit(0);
    }

    private function result_export($data)
    {
        $data_e = var_export($data, true);
        $data_e = str_replace('Requests_Utility_CaseInsensitiveDictionary::__set_state(', '', $data_e);

        $data_e = preg_replace('/^([ ]*)(.*)/m', '$1$1$2', $data_e);
        $data_r = preg_split("/\r\n|\n|\r/", $data_e);

        $data_r = preg_replace(['/\s*array\s\($/', '/\)(,)?$/', '/\s=>\s$/'], [null, ']$1', ' => ['], $data_r);
        $data = implode(\PHP_EOL, array_filter(['['] + $data_r));

        return str_replace([',', "'"], '', $data);
    }

    private function proc_wait()
    {
        if (empty($this->pids)) {
            return false;
        }

        $pids = array_keys($this->pids);
        foreach ($pids as $key) {
            if (!isset($this->pids[$key])) {
                continue;
            }

            $pid = pcntl_waitpid($this->pids[$key], $status);
            if (-1 === $pid || $pid > 0) {
                unset($this->pids[$key]);
            }

            $result = $this->proc_get($this->key, $key);

            if (!$this->args['quiet']) {
                $time = ($result['time_end'] - $result['timer_start']);
                $this->output('Executed the cron event \''.$key.'\' in '.number_format($time, 3).'s'.\PHP_EOL);
            }

            $result['pid'] = $pid;
            $this->output($this->result_export($result).\PHP_EOL.\PHP_EOL);
        }

        return $this->pids;
    }

    private function register_wpload()
    {
        if (!isset($_SERVER['HTTP_HOST'])) {
            $_SERVER['HTTP_HOST'] = '';
        }

        \define('DOING_CRON', true);

        $wpload = $this->args['wpdir'].'/wp-load.php';
        $wpcron = $this->args['dcdir'].'/includes/wp/cron.php';

        if (!@is_file($wpload)) {
            $this->output('Failed to load: '.$wpload.\PHP_EOL, true);
            exit(1);
        }

        if (!@is_file($wpcron)) {
            $this->output('Failed to load: '.$wpcron.\PHP_EOL, true);
            exit(1);
        }

        require_once $wpload;
        require_once $wpcron;
    }

    public function run()
    {
        $site = $this->strip_proto(get_home_url());
        $this->key = 'dcronwp-'.substr(md5($site), 0, 12);

        $lock_file = sys_get_temp_dir().'/'.$this->key.'-data.php';
        $stmp = time() + 120;

        if (is_file($lock_file) && is_writable($lock_file) && $stmp > @filemtime($lock_file)) {
            $this->output('Process locked, lock file: '.$lock_file."\n");
            exit(1);
        }

        add_filter(
            'dcronwp/lockfile',
            function ($lockfile) use ($lock_file) {
                return $lock_file;
            }
        );

        if ($this->args['runnow']) {
            $crons = _get_cron_array();
        } else {
            $crons = wp_get_ready_cron_jobs();
        }

        if (empty($crons)) {
            $this->output('No cron event ready to run. Try \'--run-now\' to run all now.'.\PHP_EOL);
            exit(0);
        }

        if (!$this->args['dryrun']) {
            $code = '<?php return '.var_export($crons, 1).';';
            if (!file_put_contents($lock_file, $code, \LOCK_EX)) {
                $this->output('Failed to save cron data.'.\PHP_EOL);
                exit(0);
            }
        }

        $gmt_time = microtime(true);

        $collect = [];

        foreach ($crons as $timestamp => $cronhooks) {
            if (!$this->args['runnow'] && $timestamp > $gmt_time) {
                break;
            }

            foreach ($cronhooks as $hook => $keys) {
                foreach ($keys as $k => $v) {
                    $schedule = $v['schedule'];

                    if ($schedule) {
                        dc_wp_reschedule_event($timestamp, $schedule, $hook, $v['args']);
                    }

                    dc_wp_unschedule_event($timestamp, $hook, $v['args']);

                    $collect[$hook] = $v['args'];
                }
            }
        }

        if (!empty($collect)) {
            $cnt = 0;
            $max = $this->args['job'];
            foreach ($collect as $hook => $args) {
                ++$cnt;

                $this->proc_fork(
                    $hook,
                    function () use ($hook, $args) {
                        if (!$this->args['dryrun']) {
                            $timer_start = sprintf('%.3F', microtime(true));
                            $status = true;
                            $error = '';
                            try {
                                do_action_ref_array($hook, $args);
                            } catch (\Throwable $e) {
                                $status = false;
                                $error = $e->getMessage();
                            }
                            $time_end = sprintf('%.3F', microtime(true));

                            $data = [
                                'hook' => $hook,
                                'timer_start' => $timer_start,
                                'time_end' => $time_end,
                                'status' => $status,
                            ];
                            if (!$status && !empty($error)) {
                                $data['error'] = $error;
                            }
                            $this->proc_store($this->key, $hook, $data);
                        }
                    }
                );

                if ($cnt >= $max) {
                    $this->proc_wait();
                    $cnt = 0;
                }
            }

            // cleanup
            $this->proc_wait();
        }

        if (!$this->args['dryrun']) {
            $cron = dc_getdata();
            if (!empty($cron) && \is_array($cron)) {
                _set_cron_array($cron);
            }

            if (wp_using_ext_object_cache()) {
                wp_cache_delete('alloptions', 'options');
                wp_cache_delete('cron', 'options');
            }
        }

        if (is_file($lock_file) && is_writable($lock_file)) {
            @unlink($lock_file);
        }

        exit(0);
    }
}
