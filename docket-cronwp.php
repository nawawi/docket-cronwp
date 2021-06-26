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
    exit('Docket CronWP must run from command line');
}

\defined('DOCKET_CRONWP') && exit;

\define('DOCKET_CRONWP_VERSION', '1.0.3');
\define('DOCKET_CRONWP_DIR', __DIR__);
\define('DOCKET_CRONWP', !empty($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : __FILE__);
require __DIR__.'/includes/load.php';
( new Console() )->run();
exit(0);
