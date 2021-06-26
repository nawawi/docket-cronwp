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

Download the [docket-cronwp.phar](https://github.com/nawawi/docket-cronwp/raw/main/bin/docket-cronwp.phar) using wget or curl.

```sh
wget https://github.com/nawawi/docket-cronwp/raw/main/bin/docket-cronwp.phar
```

Next, check the Phar file to verify that it’s working:

```sh
php docket-cronwp.phar --help
```

To use docket-cronwp.phar from the command line by typing docket-cronwp, make the file executable and move it to somewhere in your PATH. For example:

```sh
chmod +x docket-cronwp.phar
sudo mv docket-cronwp.phar /usr/local/bin/docket-cronwp
```

Disable the built in WordPress cron in `wp-config.php`:
```php
define( 'DISABLE_WP_CRON', true );
```

## Usage
```
docket-cronwp -h

Docket CronWP v1.0.3. Execute WordPress cron events in parallel.

Usage: docket-cronwp.php [<path>|<option>]

Options:
  -p --path <path>      Path to the WordPress files.
  -j --jobs <number>    Run number of events in parallel.
  -a --run-now          Run all cron event.
  -t --dry-run          Run without execute cron event.
  -q --quiet            Suppress informational messages.
  -h --help             Display this help and exit.
```

## Example
Run WordPress cron with 3 events execute in parallel.

```sh
docket-cronwp /path-to/wordpress --jobs 3
```

Run WordPress cron with 3 events execute in parallel every 5 minutes using server cron.  

```
*/5 * * * * root /usr/local/bin/docket-cronwp /path-to/wordpress -j3 &>/dev/null
```

Replace **root** with web server user to avoid issue with filesystem permission.

## License

Docket CronWP is an Open Source Software under the [MIT license](https://github.com/nawawi/docket-cache/blob/master/LICENSE.txt).
