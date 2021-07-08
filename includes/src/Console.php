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

final class Console
{
    use Bepart;
    use Parser;
    use Process;

    private $key;
    private $prefix = 'dcronwp-';
    private $args = [
        'dcdir' => '',
        'wpdir' => '',
        'job' => 3,
        'quiet' => false,
        'dryrun' => false,
        'network' => false,
        'runnow' => false,
        'site' => '',
        'help' => false,
        'version' => false,
        'verbose' => false,
        'debug' => false,
    ];

    public function __construct()
    {
        pcntl_async_signals(true);
        $this->register_args();
        $this->register_wpload();
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
             'name' => DOCKET_CRONWP['NAME'],
             'bin' => basename(DOCKET_CRONWP['SELF']),
             'version' => DOCKET_CRONWP['VERSION'],
             'path' => DOCKET_CRONWP['ROOT'],
         ];
    }

    private function print_banner()
    {
        $this->output(\PHP_EOL.sprintf('%s v%s.', $this->app()->name, $this->app()->version).\PHP_EOL.'Execute WordPress cron events in parallel.'.\PHP_EOL.\PHP_EOL);
    }

    private function print_usage()
    {
        $text = '';
        $text .= 'Usage:'.\PHP_EOL;
        $text .= '  '.$this->app()->bin.' [<path>|<options>]'.\PHP_EOL.\PHP_EOL;
        $text .= 'Path:'.\PHP_EOL;
        $text .= '  Path to the WordPress files.'.\PHP_EOL.\PHP_EOL;
        $text .= 'Options:'.\PHP_EOL;
        $text .= '  -p --path <path>      Path to the WordPress files.'.\PHP_EOL;
        $text .= '  -j --jobs <number>    Run number of events in parallel (default: '.$this->args['job'].').'.\PHP_EOL;
        $text .= '  -u --url <url>        Multisite target URL.'.\PHP_EOL;
        $text .= '  -a --run-now          Run all cron event.'.\PHP_EOL;
        $text .= '  -t --dry-run          Run without execute cron event.'.\PHP_EOL;
        $text .= '  -q --quiet            Suppress output.'.\PHP_EOL;
        $text .= '  -v --verbose          Display additional output.'.\PHP_EOL;
        $text .= '     --debug            Display debugging output.'.\PHP_EOL;
        $text .= '  -V --version          Display version.'.\PHP_EOL;
        $text .= '  -h --help             Display this help message.'.\PHP_EOL;
        $this->output($text);
    }

    private function print_args()
    {
        $text = $this->rowh($this->app()->name).': '.$this->app()->version.\PHP_EOL;
        $text .= $this->rowh('Path').': '.$this->args['wpdir'].\PHP_EOL;
        $text .= $this->rowh('Jobs').': '.$this->args['job'].\PHP_EOL;
        $text .= \PHP_EOL;
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
                    $this->args['dryrun'] = $this->get_bool($key, false);
                    break;
                case 'q':
                case 'quiet':
                    $this->args['quiet'] = $this->get_bool($key, false);
                    break;
                case 'a':
                case 'run-now':
                    $this->args['runnow'] = $this->get_bool($key, false);
                    break;
                case 'h':
                case 'help':
                    $this->args['help'] = $this->get_bool($key, false);
                    break;
                case 'V':
                case 'version':
                    $this->args['version'] = $this->get_bool($key, false);
                    break;
                case 'v':
                case 'verbose':
                    $this->args['verbose'] = $this->get_bool($key, false);
                    break;
                case 'debug':
                    $this->args['debug'] = $this->get_bool($key, false);
                    break;
                case 'j':
                case 'jobs':
                    $job = (int) $value;
                    $this->args['job'] = $job < 0 ? 1 : $job;
                    break;
                case 'u':
                case 'url':
                    if (!$this->get_bool($key, false)) {
                        $this->args['url'] = $value;
                    }
                    break;
            }
        }

        if ($this->args['version']) {
            $this->output($this->app()->version.\PHP_EOL);
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
            $this->output('No WordPress installation found, run '.$this->app()->bin.' `path/to/wordpress`.'.\PHP_EOL, true);
            $this->output('Run '.$this->app()->bin.' --help for more options.'.\PHP_EOL, true);
            exit(1);
        }

        if (!empty($this->args['url']) && !filter_var($this->args['url'], \FILTER_VALIDATE_URL)) {
            $this->output('Invalid url '.$this->args['url'].\PHP_EOL, true);
            exit(1);
        }
    }

    private function register_wpload()
    {
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

        $_SERVER['HTTP_HOST'] = '';
        $_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.0';
        $_SERVER['HTTP_USER_AGENT'] = '';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';

        if (!empty($this->args['url'])) {
            $_SERVER['HTTP_HOST'] = parse_url($this->args['url'], \PHP_URL_HOST);
        }

        \define('DOING_CRON', true);

        require_once $wpload;
        require_once $wpcron;

        $this->wpdb_suppress_errors();
    }

    private function cleanup()
    {
        $files = glob($this->lockpath().$this->prefix.'*.php', \GLOB_MARK | \GLOB_NOSORT);
        if (!empty($files) && \is_array($files)) {
            foreach ($files as $file) {
                if (@is_file($file) && @is_writable($file)) {
                    @unlink($file);
                }
            }
        }
    }

    private function crons_now($crons)
    {
        $gmt_time = microtime(true);
        $keys = array_keys($crons);
        if (isset($keys[0]) && $keys[0] > $gmt_time) {
            return [];
        }

        $results = [];
        foreach ($crons as $timestamp => $cronhooks) {
            if ($timestamp > $gmt_time) {
                break;
            }
            $results[$timestamp] = $cronhooks;
        }

        return $results;
    }

    public function run()
    {
        $site = $this->strip_proto(get_home_url());
        $this->key = $this->prefix.$this->get_hash($site);

        $lock_file = $this->lockpath().$this->key.'-data.php';
        $stmp = time() + 3600;

        if (is_file($lock_file) && is_readable($lock_file) && $stmp > @filemtime($lock_file)) {
            $this->output('Process locked, lock file: '.$lock_file.\PHP_EOL);
            exit(1);
        }

        add_filter(
            'dcronwp/lockfile',
            function ($lockfile) use ($lock_file) {
                return $lock_file;
            }
        );

        $crons = _get_cron_array();

        if (!$this->args['runnow'] && !empty($crons) && \is_array($crons)) {
            $crons_now = $this->crons_now($crons);
            if (empty($crons_now)) {
                $this->output('No cron event ready to run. Try \'--run-now\' to run all now.'.\PHP_EOL);
                exit(0);
            }
        }

        if (empty($crons)) {
            $this->output('No cron event available.'.\PHP_EOL);
            exit(0);
        }

        // ctrl+c
        pcntl_signal(\SIGINT, function ($signo) use ($lock_file) {
            static $done = false;

            if (!$done) {
                $done = true;
                $this->output('Exiting.. cleanup lock files.'.\PHP_EOL);
                if (is_file($lock_file) && is_writable($lock_file)) {
                    @unlink($lock_file);
                }
                $this->cleanup();
            }
            exit(0);
        });

        if (!$this->args['dryrun']) {
            $code = '<?php return '.var_export($crons, 1).';';
            if (!file_put_contents($lock_file, $code, \LOCK_EX)) {
                $this->output('Failed to save cron data.'.\PHP_EOL);
                exit(0);
            }
        }

        if (!$this->args['quiet'] && $this->args['verbose']) {
            $this->print_args();
        }

        $wp_get_schedules = wp_get_schedules();
        add_filter(
            'dcronwp/wp_get_schedules',
            function ($arr) use ($wp_get_schedules) {
                return $wp_get_schedules;
            }
        );

        $lock_spawn = !\defined('DISABLE_WP_CRON') || !DISABLE_WP_CRON;
        if ($lock_spawn) {
            $lock_wp_cron = microtime(true) + 300;
            set_transient('doing_cron', $lock_wp_cron, 86400);
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
                        if (false === dc_wp_reschedule_event($timestamp, $schedule, $hook, $v['args'])) {
                            continue;
                        }
                    }

                    if (false === dc_wp_unschedule_event($timestamp, $hook, $v['args'])) {
                        continue;
                    }

                    $collect[$hook] = $v['args'];
                }
            }
        }

        unset($crons, $cronhooks);

        $mypid = getmypid();
        $this->output_debug('Process-begin', $mypid, 'Processing '.\count($collect).' events, where every '.$this->args['job'].' events are run in parallel.');

        if (!empty($collect)) {
            $cnt = 0;
            $max = $this->args['job'];
            $num = 0;
            $all = \count($collect);
            foreach ($collect as $hook => $args) {
                ++$cnt;
                ++$num;
                $this->proc_fork(
                    $hook,
                    function () use ($hook, $args) {
                        $timer_start = sprintf('%.4F', microtime(true));
                        $status = true;
                        $error = '';
                        $content = '';

                        if (!$this->args['dryrun']) {
                            try {
                                ob_start();
                                do_action_ref_array($hook, $args);
                                $content = trim(ob_get_contents());
                                ob_end_clean();
                            } catch (\Throwable $e) {
                                $status = false;
                                $error = $e->getMessage();
                            }
                        }

                        $timer_stop = sprintf('%.4F', microtime(true));

                        $data = [
                            'pid' => '',
                            'hook' => $hook,
                            'time_gmt' => date('Y-m-d H:i:s', $timer_start),
                            'timer_start' => $timer_start,
                            'timer_stop' => $timer_stop,
                            'status' => $this->args['dryrun'] ? 'dry-run' : $status ? 'success' : 'failure',
                        ];

                        if ('' !== $content) {
                            $data['output'] = $content;
                        }

                        if (!$status && !empty($error)) {
                            $data['error'] = $error;
                        }

                        $this->proc_store($this->key, $hook, $data);
                    }
                );

                if ($cnt >= $max) {
                    $this->output_debug('Wait-begin', $mypid, 'Waiting '.$cnt.' events to finish.');
                    $this->proc_wait();

                    $nx = $all - $num;
                    if ($nx > $max) {
                        $nx = $cnt;
                    }

                    if ($nx > 0) {
                        $this->output_debug('Wait-done', $mypid, 'Processing next '.$nx.' events.');
                    }

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

        $this->cleanup();

        if ($lock_spawn) {
            delete_transient('doing_cron');
        }

        $this->output_debug('Process-done', $mypid, 'Exiting.');
        exit(0);
    }
}
