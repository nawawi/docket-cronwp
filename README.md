![Docket CronWP](./.docketcache.com/icon-128x128.png)
# Docket CronWP

Execute WordPress cron events in parallel.

## Description

Docket CronWP is a command-line tool for executing WordPress cron events in parallel.

This tool is part of the [Docket Cache](https://docketcache.com) project.

## Requirements
- UNIX-like environment
- PHP >= 7.2.5
- WordPress >= 5.4
- PHP [pnctl](https://www.php.net/manual/en/book.pcntl.php) extension

## Installation

Download the [docket-cronwp.phar](https://github.com/nawawi/docket-cronwp/raw/main/bin/docket-cronwp.phar) using `wget` or `curl`.

```sh
wget https://github.com/nawawi/docket-cronwp/raw/main/bin/docket-cronwp.phar
```

Next, check the Phar file to verify that it’s working:

```sh
php docket-cronwp.phar --help
```

To use `docket-cronwp.phar` from the command line by typing `cronwp`, make the file executable and move it to somewhere in your PATH. For example:

```sh
chmod +x docket-cronwp.phar
sudo mv docket-cronwp.phar /usr/local/bin/cronwp
```

Disable the built in WordPress cron in `wp-config.php`:
```php
define( 'DISABLE_WP_CRON', true );
```

Updating to the latest version:

```sh
sudo wget https://github.com/nawawi/docket-cronwp/raw/main/bin/docket-cronwp.phar -O /usr/local/bin/cronwp
sudo chmod +x /usr/local/bin/cronwp

```

## Usage
```
$ cronwp -h

Docket CronWP v1.2.
Execute WordPress cron events in parallel.

Usage:
  docket-cronwp.phar [<path>|<options>]

Path:
  Path to the WordPress files.

Options:
  -p --path <path>      Path to the WordPress files.
  -j --jobs <number>    Run number of events in parallel (default: 3).
  -u --url <url>        Multisite target URL.
  -a --run-now          Run all cron event.
  -t --dry-run          Run without execute cron event.
  -q --quiet            Suppress output.
  -v --verbose          Display additional output.
     --debug            Display debugging output.
  -V --version          Display version.
  -h --help             Display this help message.
```

## Example
Run WordPress cron with 3 events execute in parallel:

```sh
cronwp /path-to/wordpress --jobs 3
```

Or within the WordPress installation directory.

```sh
cd /path-to/wordpress
cronwp --jobs 3
```

Output:
```
Executed the cron event 'wp_privacy_delete_old_export_files' in 0.000s
Executed the cron event 'wp_https_detection' in 0.004s
Executed the cron event 'wp_version_check' in 3.476s
Executed the cron event 'wp_update_plugins' in 2.721s
Executed the cron event 'wp_update_themes' in 0.004s
Executed the cron event 'recovery_mode_clean_expired_keys' in 0.000s
Executed the cron event 'wp_scheduled_delete' in 0.001s
Executed the cron event 'delete_expired_transients' in 0.000s
Executed the cron event 'wp_site_health_scheduled_check' in 2.932s
```

Run with additional output:

```sh
cronwp /path-to/wordpress --jobs 3 --verbose
```

Output:
```
Docket CronWP  : 1.2
Path           : /path-to/wordpress
Jobs           : 3

Executed the cron event 'wp_privacy_delete_old_export_files' in 0.001s
pid            : 23093
hook           : wp_privacy_delete_old_export_files
time_gmt       : 2021-07-04 09:26:01
timer_start    : 1625390761.8847
timer_stop     : 1625390761.8853
status         : success

Executed the cron event 'wp_https_detection' in 0.004s
pid            : 23094
hook           : wp_https_detection
time_gmt       : 2021-07-04 09:26:01
timer_start    : 1625390761.907
timer_stop     : 1625390761.9111
status         : success

Executed the cron event 'wp_version_check' in 3.468s
pid            : 23096
hook           : wp_version_check
time_gmt       : 2021-07-04 09:26:01
timer_start    : 1625390761.9313
timer_stop     : 1625390765.3998
status         : success

Executed the cron event 'wp_update_plugins' in 2.666s
pid            : 23098
hook           : wp_update_plugins
time_gmt       : 2021-07-04 09:26:05
timer_start    : 1625390765.4321
timer_stop     : 1625390768.0978
status         : success

Executed the cron event 'wp_update_themes' in 0.005s
pid            : 23100
hook           : wp_update_themes
time_gmt       : 2021-07-04 09:26:08
timer_start    : 1625390768.1265
timer_stop     : 1625390768.1311
status         : success

....
```

Run with debugging output:

```sh
cronwp /path-to/wordpress --jobs 3 --run-now --quiet --debug
```

Output: _( # Timestamp : Label : PID : Message )_
```
# 1625731052.7421 : Process-begin  : 53132  : Processing 15 events, where every 3 events are run in parallel.
# 1625731052.7432 : Forked         : 53133  : for event 'docketcache_gc'
# 1625731052.7434 : Callback-begin : 53133  : for event 'docketcache_gc'.
# 1625731052.7833 : Callback-done  : 53133  : for event 'docketcache_gc'.
# 1625731052.8051 : Parent-closed  : 53133  : for event 'docketcache_gc'.
# 1625731052.8063 : Forked         : 53134  : for event 'docketcache_watchproc'
# 1625731052.8064 : Callback-begin : 53134  : for event 'docketcache_watchproc'.
# 1625731052.8501 : Callback-done  : 53134  : for event 'docketcache_watchproc'.
# 1625731052.8698 : Parent-closed  : 53134  : for event 'docketcache_watchproc'.
# 1625731052.8709 : Forked         : 53135  : for event 'wp_privacy_delete_old_export_files'
# 1625731052.8710 : Callback-begin : 53135  : for event 'wp_privacy_delete_old_export_files'.
# 1625731052.8715 : Callback-done  : 53135  : for event 'wp_privacy_delete_old_export_files'.
# 1625731052.8918 : Parent-closed  : 53135  : for event 'wp_privacy_delete_old_export_files'.
# 1625731052.8923 : Wait-begin     : 53132  : Waiting 3 events to finish.
# 1625731052.8923 : Child-closed   : 53133  : for event 'docketcache_gc'.
# 1625731052.8926 : Child-closed   : 53134  : for event 'docketcache_watchproc'.
# 1625731052.8927 : Child-closed   : 53135  : for event 'wp_privacy_delete_old_export_files'.
# 1625731052.8927 : Wait-done      : 53132  : Processing next 3 events.

....
```
<br>

Run WordPress cron with 3 events execute in parallel using server cron. Edit `/etc/crontab` and insert command below: 

```
* * * * * apache /usr/local/bin/cronwp /path-to/wordpress -q -j 3 &>/dev/null
```

Replace **apache** with web server or php-fpm user to avoid issue with filesystem permission.

## Contributions

Please open an [issue](https://github.com/nawawi/docket-cronwp/issues) to report a bug or discuss what you would like to change.

## Sponsor this project

Fund Docket CronWP one-off or recurring payment to support open-source development efforts.  

[![PayPal](./.docketcache.com/paypalme.png)](https://www.paypal.com/paypalme/ghostbirdme/10usd) 
[![SecurePay Malaysia](./.docketcache.com/securepay.png)](https://securepay.my/collections/docketcacheproject) 
[![Bitcoin](./.docketcache.com/bitcoin.png)](https://www.blockchain.com/en/btc/address/3BD96JehFzsdFv4MTmvvgVhfVFLC86414n)

## License

Docket CronWP is an Open Source Software under the [MIT license](https://github.com/nawawi/docket-cache/blob/master/LICENSE.txt).
