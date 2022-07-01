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

/*
 * Reference:
 *  wp-includes/cron.php.
 */
final class Cron
{
    public static function getdata()
    {
        $file = apply_filters('docketcronwp/lockfile', false);
        if (empty($file) || !is_file($file)) {
            return false;
        }

        $data = include $file;
        if (!empty($data) && \is_array($data)) {
            return $data;
        }

        return false;
    }

    public static function putdata($data)
    {
        $file = apply_filters('docketcronwp/lockfile', false);
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

    // wp_schedule_event
    public static function schedule_event($timestamp, $recurrence, $hook, $args = [])
    {
        if (!is_numeric($timestamp) || $timestamp <= 0) {
            return false;
        }

        $schedules = self::get_schedules();

        if (!isset($schedules[$recurrence])) {
            return false;
        }

        $event = (object) [
            'hook' => $hook,
            'timestamp' => $timestamp,
            'schedule' => $recurrence,
            'args' => $args,
            'interval' => $schedules[$recurrence]['interval'],
        ];

        $pre = apply_filters('pre_schedule_event', null, $event, false);

        if (null !== $pre) {
            if (is_wp_error($pre)) {
                return false;
            }

            return $pre;
        }

        $event = apply_filters('schedule_event', $event);

        if (!$event) {
            return false;
        }

        $key = md5(serialize($event->args));

        $crons = self::get_cron_array();
        $crons[$event->timestamp][$event->hook][$key] = [
            'schedule' => $event->schedule,
            'args' => $event->args,
            'interval' => $event->interval,
        ];
        uksort($crons, 'strnatcasecmp');

        return self::set_cron_array($crons);
    }

    // wp_reschedule_event
    public static function reschedule_event($timestamp, $recurrence, $hook, $args = [])
    {
        if (!is_numeric($timestamp) || $timestamp <= 0) {
            return false;
        }

        $schedules = self::get_schedules();
        $interval = 0;

        if (isset($schedules[$recurrence])) {
            $interval = $schedules[$recurrence]['interval'];
        }

        if (0 === $interval) {
            $scheduled_event = self::get_scheduled_event($hook, $args, $timestamp);
            if ($scheduled_event && isset($scheduled_event->interval)) {
                $interval = $scheduled_event->interval;
            }
        }

        $event = (object) [
            'hook' => $hook,
            'timestamp' => $timestamp,
            'schedule' => $recurrence,
            'args' => $args,
            'interval' => $interval,
        ];

        $pre = apply_filters('pre_reschedule_event', null, $event, false);

        if (null !== $pre) {
            if (is_wp_error($pre)) {
                return false;
            }

            return $pre;
        }

        if (0 == $interval) {
            return false;
        }

        $now = time();

        if ($timestamp >= $now) {
            $timestamp = $now + $interval;
        } else {
            $timestamp = $now + ($interval - (($now - $timestamp) % $interval));
        }

        return self::schedule_event($timestamp, $recurrence, $hook, $args);
    }

    // wp_unschedule_event
    public static function unschedule_event($timestamp, $hook, $args = [])
    {
        if (!is_numeric($timestamp) || $timestamp <= 0) {
            return false;
        }

        $pre = apply_filters('pre_unschedule_event', null, $timestamp, $hook, $args, false);

        if (null !== $pre) {
            if (is_wp_error($pre)) {
                return false;
            }

            return $pre;
        }

        $crons = self::get_cron_array();
        $key = md5(serialize($args));
        unset($crons[$timestamp][$hook][$key]);
        if (empty($crons[$timestamp][$hook])) {
            unset($crons[$timestamp][$hook]);
        }
        if (empty($crons[$timestamp])) {
            unset($crons[$timestamp]);
        }

        return self::set_cron_array($crons);
    }

    // wp_get_scheduled_event
    public static function get_scheduled_event($hook, $args = [], $timestamp = null)
    {
        $pre = apply_filters('pre_get_scheduled_event', null, $hook, $args, $timestamp);
        if (null !== $pre) {
            return $pre;
        }

        if (null !== $timestamp && !is_numeric($timestamp)) {
            return false;
        }

        $crons = self::get_cron_array();
        if (empty($crons)) {
            return false;
        }

        $key = md5(serialize($args));

        if (!$timestamp) {
            $next = false;
            foreach ($crons as $timestamp => $cron) {
                if (isset($cron[$hook][$key])) {
                    $next = $timestamp;
                    break;
                }
            }
            if (!$next) {
                return false;
            }

            $timestamp = $next;
        } elseif (!isset($crons[$timestamp][$hook][$key])) {
            return false;
        }

        $event = (object) [
            'hook' => $hook,
            'timestamp' => $timestamp,
            'schedule' => $crons[$timestamp][$hook][$key]['schedule'],
            'args' => $args,
        ];

        if (isset($crons[$timestamp][$hook][$key]['interval'])) {
            $event->interval = $crons[$timestamp][$hook][$key]['interval'];
        }

        return $event;
    }

    // wp_get_schedules
    public static function get_schedules()
    {
        return apply_filters('docketcronwp/upstream_get_schedules', []);
    }

    // _get_cron_array
    public static function get_cron_array()
    {
        $cron = self::getdata();
        if (!\is_array($cron)) {
            return false;
        }

        unset($cron['version']);

        return $cron;
    }

    // __set_cron_array
    public static function set_cron_array($cron)
    {
        $cron['version'] = 2;
        $result = self::putdata($cron);

        return $result;
    }
}
