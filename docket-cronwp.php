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

\define('DOCKET_CRONWP_VERSION', '1.0.1');
\define('DOCKET_CRONWP', realpath(__DIR__));
require __DIR__.'/includes/load.php';
( new Console() )->run();
exit(0);
