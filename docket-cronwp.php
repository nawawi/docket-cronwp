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

if ('cli' !== \PHP_SAPI) {
    echo 'Only CLI access.'.\PHP_EOL;
    exit(1);
}

if (\defined('DOCKET_CRONWP')) {
    echo 'Invalid access.'.\PHP_EOL;
    exit(1);
}

if (!(\PHP_VERSION_ID >= 70205)) {
    printf('Error: Docket-CronWP requires PHP %s or newer. You are running version %s.'.\PHP_EOL, '7.2.5', \PHP_VERSION);
    exit(1);
}

if (!\extension_loaded('pcntl') || !\function_exists('pcntl_fork')) {
    printf("Error: Docket CronWP requires '%s' extension.".\PHP_EOL, 'pcntl');
    exit(1);
}

\define(
    'DOCKET_CRONWP',
    [
        'NAME' => 'Docket CronWP',
        'VERSION' => '1.0.8',
        'ROOT' => __DIR__,
        'SELF' => !empty($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : __FILE__,
    ]
);

require __DIR__.'/includes/load.php';
( new Console() )->run();
exit(0);
