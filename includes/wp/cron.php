<?php
/**
 * Docket CronWP.
 *
 * @author  Nawawi Jamili
 * @license MIT
 *
 * @see    https://github.com/nawawi/docket-cronwp
 */

/*
 * Reference:
 *  wp-includes/cron.php.
 */
\defined('DOCKET_CRONWP') || exit;

function dc_getdata()
{
    $file = apply_filters('dcronwp/data', false);
    if (!is_file($file)) {
        return false;
    }

    $data = include $file;
    if (!empty($data) && \is_array($data)) {
        return $data;
    }

    return false;
}

function dc_putdata($data)
{
    $file = apply_filters('dcronwp/data', false);
    if (!is_file($file)) {
        return false;
    }

    if (empty($data) || !\is_array($data)) {
        return false;
    }

    $code = '<?php return '.var_export($data, 1).';';

    return file_put_contents($file, $code, \LOCK_EX);
}

function dc_wp_schedule_event($timestamp, $recurrence, $hook, $args = [], $wp_error = false)
{
    if (!is_numeric($timestamp) || $timestamp <= 0) {
        if ($wp_error) {
            return new WP_Error(
                'invalid_timestamp',
                __('Event timestamp must be a valid Unix timestamp.')
            );
        }

        return false;
    }

    $schedules = dc_wp_get_schedules();

    if (!isset($schedules[$recurrence])) {
        if ($wp_error) {
            return new WP_Error(
                'invalid_schedule',
                __('Event schedule does not exist.')
            );
        }

        return false;
    }

    $event = (object) [
        'hook' => $hook,
        'timestamp' => $timestamp,
        'schedule' => $recurrence,
        'args' => $args,
        'interval' => $schedules[$recurrence]['interval'],
    ];

    $pre = apply_filters('pre_schedule_event', null, $event, $wp_error);

    if (null !== $pre) {
        if ($wp_error && false === $pre) {
            return new WP_Error(
                'pre_schedule_event_false',
                __('A plugin prevented the event from being scheduled.')
            );
        }

        if (!$wp_error && is_wp_error($pre)) {
            return false;
        }

        return $pre;
    }

    $event = apply_filters('schedule_event', $event);

    if (!$event) {
        if ($wp_error) {
            return new WP_Error(
                'schedule_event_false',
                __('A plugin disallowed this event.')
            );
        }

        return false;
    }

    $key = md5(serialize($event->args));

    $crons = dc__get_cron_array();
    $crons[$event->timestamp][$event->hook][$key] = [
        'schedule' => $event->schedule,
        'args' => $event->args,
        'interval' => $event->interval,
    ];
    uksort($crons, 'strnatcasecmp');

    return dc__set_cron_array($crons, $wp_error);
}

function dc_wp_reschedule_event($timestamp, $recurrence, $hook, $args = [], $wp_error = false)
{
    if (!is_numeric($timestamp) || $timestamp <= 0) {
        if ($wp_error) {
            return new WP_Error(
                'invalid_timestamp',
                __('Event timestamp must be a valid Unix timestamp.')
            );
        }

        return false;
    }

    $schedules = dc_wp_get_schedules();
    $interval = 0;

    if (isset($schedules[$recurrence])) {
        $interval = $schedules[$recurrence]['interval'];
    }

    if (0 === $interval) {
        $scheduled_event = dc_wp_get_scheduled_event($hook, $args, $timestamp);
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

    $pre = apply_filters('pre_reschedule_event', null, $event, $wp_error);

    if (null !== $pre) {
        if ($wp_error && false === $pre) {
            return new WP_Error(
                'pre_reschedule_event_false',
                __('A plugin prevented the event from being rescheduled.')
            );
        }

        if (!$wp_error && is_wp_error($pre)) {
            return false;
        }

        return $pre;
    }

    if (0 == $interval) {
        if ($wp_error) {
            return new WP_Error(
                'invalid_schedule',
                __('Event schedule does not exist.')
            );
        }

        return false;
    }

    $now = time();

    if ($timestamp >= $now) {
        $timestamp = $now + $interval;
    } else {
        $timestamp = $now + ($interval - (($now - $timestamp) % $interval));
    }

    return dc_wp_schedule_event($timestamp, $recurrence, $hook, $args, $wp_error);
}

function dc_wp_unschedule_event($timestamp, $hook, $args = [], $wp_error = false)
{
    if (!is_numeric($timestamp) || $timestamp <= 0) {
        if ($wp_error) {
            return new WP_Error(
                'invalid_timestamp',
                __('Event timestamp must be a valid Unix timestamp.')
            );
        }

        return false;
    }

    $pre = apply_filters('pre_unschedule_event', null, $timestamp, $hook, $args, $wp_error);

    if (null !== $pre) {
        if ($wp_error && false === $pre) {
            return new WP_Error(
                'pre_unschedule_event_false',
                __('A plugin prevented the event from being unscheduled.')
            );
        }

        if (!$wp_error && is_wp_error($pre)) {
            return false;
        }

        return $pre;
    }

    $crons = dc__get_cron_array();
    $key = md5(serialize($args));
    unset($crons[$timestamp][$hook][$key]);
    if (empty($crons[$timestamp][$hook])) {
        unset($crons[$timestamp][$hook]);
    }
    if (empty($crons[$timestamp])) {
        unset($crons[$timestamp]);
    }

    return dc__set_cron_array($crons, $wp_error);
}

function dc_wp_get_scheduled_event($hook, $args = [], $timestamp = null)
{
    $pre = apply_filters('pre_get_scheduled_event', null, $hook, $args, $timestamp);
    if (null !== $pre) {
        return $pre;
    }

    if (null !== $timestamp && !is_numeric($timestamp)) {
        return false;
    }

    $crons = dc__get_cron_array();
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

function dc_wp_get_schedules()
{
    $schedules = [
        'hourly' => [
            'interval' => HOUR_IN_SECONDS,
            'display' => __('Once Hourly'),
        ],
        'twicedaily' => [
            'interval' => 12 * HOUR_IN_SECONDS,
            'display' => __('Twice Daily'),
        ],
        'daily' => [
            'interval' => DAY_IN_SECONDS,
            'display' => __('Once Daily'),
        ],
        'weekly' => [
            'interval' => WEEK_IN_SECONDS,
            'display' => __('Once Weekly'),
        ],
    ];

    return array_merge(apply_filters('cron_schedules', []), $schedules);
}

function dc_wp_get_ready_cron_jobs()
{
    $pre = apply_filters('pre_get_ready_cron_jobs', null);
    if (null !== $pre) {
        return $pre;
    }

    $crons = dc__get_cron_array();

    if (false === $crons) {
        return [];
    }

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

function dc__get_cron_array()
{
    $cron = dc_getdata();
    if (!\is_array($cron)) {
        return false;
    }

    if (!isset($cron['version'])) {
        $cron = dc__upgrade_cron_array($cron);
    }

    unset($cron['version']);

    return $cron;
}

function dc__set_cron_array($cron, $wp_error = false)
{
    $cron['version'] = 2;
    $result = dc_putdata($cron);

    if ($wp_error && !$result) {
        return new WP_Error(
            'could_not_set',
            __('The cron event list could not be saved.')
        );
    }

    return $result;
}

function dc__upgrade_cron_array($cron)
{
    $cron['version'] = 2;

    return $cron;
}
