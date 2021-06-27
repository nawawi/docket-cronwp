# ![Docket CronWP](./.docketcache.com/icon-128x128.png) Docket CronWP

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

Docket CronWP v1.0.4
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

Results:
```
Executed the cron event 'wp_https_detection' in 0.006s
[
    hook => wp_https_detection
    timer_start => 1624752922.008
    timer_stop => 1624752922.014
    status => true
    pid => 350128
]

Executed the cron event 'wp_update_plugins' in 0.094s
[
    hook => wp_update_plugins
    timer_start => 1624752922.917
    timer_stop => 1624752923.011
    status => true
    pid => 350135
]
```

Run WordPress cron with 3 events execute in parallel every 5 minutes using server cron: 

```
*/5 * * * * root /usr/local/bin/cronwp /path-to/wordpress -j3 &>/dev/null
```

Replace **root** with web server user to avoid issue with filesystem permission.

## License

Docket CronWP is an Open Source Software under the [MIT license](https://github.com/nawawi/docket-cache/blob/master/LICENSE.txt).
