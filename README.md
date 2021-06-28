![Docket CronWP](./.docketcache.com/icon-128x128.png)
# Docket CronWP

Execute WordPress cron events in parallel.

## Description

Docket CronWP is a command-line tool for executing WordPress cron events in parallel.

## Requirements
- UNIX-like environment (OS X, Linux, FreeBSD, Cygwin, WSL)
- PHP >= 7.2.5
- WordPress >= 5.4
- PHP pnctl extension

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

## Usage
```
cronwp -h

Docket CronWP v1.0.5
Execute WordPress cron events in parallel.

Usage:
  cronwp [<path>|<options>]

Path:
  Path to the WordPress files.

Options:
  -p --path <path>      Path to the WordPress files.
  -j --jobs <number>    Run number of events in parallel.
  -a --run-now          Run all cron event.
  -t --dry-run          Run without execute cron event.
  -h --help             Display this help message.
  -q --quiet            Suppress informational messages.
  -V --version          Display version.
```

## Example
Run WordPress cron with 3 events execute in parallel:

```sh
cronwp /path-to/wordpress --jobs 3
```

Output:
```
Executed the cron event 'wp_update_plugins' in 2.163s
[
    pid => 43406
    time => 2021-06-28 02:14:53
    hook => wp_update_plugins
    timer_start => 1624846493.252183
    timer_stop => 1624846495.415512
    status => true
]

Executed the cron event 'wp_update_themes' in 0.006s
[
    pid => 43407
    time => 2021-06-28 02:14:53
    hook => wp_update_themes
    timer_start => 1624846493.253142
    timer_stop => 1624846493.259058
    status => true
]

Executed the cron event 'wp_scheduled_delete' in 0.003s
[
    pid => 43408
    time => 2021-06-28 02:14:53
    hook => wp_scheduled_delete
    timer_start => 1624846493.254066
    timer_stop => 1624846493.256793
    status => true
]

Executed the cron event 'delete_expired_transients' in 0.000s
[
    pid => 43418
    time => 2021-06-28 02:14:55
    hook => delete_expired_transients
    timer_start => 1624846495.438424
    timer_stop => 1624846495.438591
    status => true
]
```

Run WordPress cron with 3 events execute in parallel every 5 minutes using server cron: 

```
*/5 * * * * root /usr/local/bin/cronwp /path-to/wordpress -j3 &>/dev/null
```

Replace **root** with web server user to avoid issue with filesystem permission.

## License

Docket CronWP is an Open Source Software under the [MIT license](https://github.com/nawawi/docket-cache/blob/master/LICENSE.txt).
