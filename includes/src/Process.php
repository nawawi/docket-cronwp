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

trait Process
{
    private $pids = [];

    private function proc_store($key, $hook, $data)
    {
        $file = $this->lockpath().$this->key.'-'.$this->get_hash($hook).'.php';

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
        $file = $this->lockpath().$this->key.'-'.$this->get_hash($hook).'.php';
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

    private function proc_output($data)
    {
        $output = '';
        foreach ($data as $name => $value) {
            $output .= $this->rowh($name).': '.$value.\PHP_EOL;
        }

        return $output;
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

            $this->output_debug('Forked', $pid, "for event '".$name."'");

            // prevent zombie
            pcntl_wait($status);

            $this->output_debug('Parent-closed', $pid, "for event '".$name."'.");

            // we're parent, db already close, reconnect
            $this->wpdb_reconnect();

            return true;
        }

        $pid = getmypid();
        $this->output_debug('Callback-begin', $pid, "for event '".$name."'.");

        \call_user_func($callback);

        $this->output_debug('Callback-done', $pid, "for event '".$name."'.");
        exit(0);
    }

    private function proc_wait()
    {
        if (empty($this->pids)) {
            return false;
        }

        $pids = array_keys($this->pids);
        foreach ($pids as $name) {
            if (!isset($this->pids[$name])) {
                continue;
            }

            $pid = $this->pids[$name];
            pcntl_waitpid($pid, $status);
            unset($this->pids[$name]);

            $this->output_debug('Child-closed', $pid, "for event '".$name."'.");

            $result = $this->proc_get($this->key, $name);
            if (!empty($result) && \is_array($result)) {
                if (!$this->args['quiet']) {
                    $time = ($result['timer_stop'] - $result['timer_start']);
                    $this->output('Executed the cron event \''.$name.'\' in '.number_format($time, 3).'s.'.\PHP_EOL);
                    if ($this->args['verbose']) {
                        $result['pid'] = $pid;
                        $this->output($this->proc_output($result).\PHP_EOL);
                    }
                }
            }
        }

        return $this->pids;
    }
}
