# ![Docket CronWP](./.docketcache.com/icon-128x128.png) Docket CronWP

Execute WordPress cron events in parallel.

## Description

Docket CronWP is a command-line tool for executing WordPress cron events in parallel.

## Requirements

- PHP >= 7.2.5
- WordPress >= 5.4
- PHP pnctl extension

## Installation
```
composer create-project nawawi/docket-cronwp
```
## Usage
```
php docket-cronwp.php -h
Docket CronWP v1.0.1. Execute WordPress cron events in parallel.

Usage: docket-cronwp [<path>|<option>]

Options:
  -p --path <path>      Path to the WordPress files.
  -j --jobs <number>    Run number of jobs in parallel.
  -a --run-now          Run all cron event.
  -t --dry-run          Run without execute cron event.
  -q --quiet            Suppress informational messages.
  -h --help             Display this help and exit.

```

## Example
Run WordPress cron with 3 events execute in parallel.
```
php docket-cronwp.php /path/to/wordpress --jobs 3
```
Run WordPress cron with 3 events execute in parallel every 5 minutes using server cron.  
```
*/5 * * * * root /usr/bin/php -f /opt/docket-cronwp/docket-cronwp.php /opt/webapp/wordpress -j3 &>/dev/null
```
Replace **root** with web server user to avoid filesystem permission issue.

## License

Docket CronWP is an Open Source Software under the [MIT license](https://github.com/nawawi/docket-cache/blob/master/LICENSE.txt).
