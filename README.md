# Adminer Updater

Install or update a local Adminer PHP file from the command line. The updater checks the latest version on adminer.org and downloads the full upstream release from GitHub when needed.

## Requirements

- PHP CLI 7.4 or newer with HTTPS stream support and `allow_url_fopen` enabled.
- Outbound HTTPS access to adminer.org and GitHub release downloads.
- Permission to read the existing Adminer file and write to its parent directory.

## Install or update

```sh
git clone https://github.com/sunfoxcz/adminer-updater.git
cd adminer-updater
php update-adminer.php -v /var/www/html/adminer.php
```

Replace `/var/www/html/adminer.php` with the path to your Adminer file. Relative paths resolve from the current working directory. Use `--` before a path that starts with a hyphen.

If the file is missing, the updater installs Adminer there. It skips a file that is already up to date. When replacing an older version, it attempts to back up the previous file as `<path>.bak`.

## Run from cron

Use absolute paths in your crontab. This example checks for updates daily at 03:15.

```cron
15 3 * * * /usr/bin/php /opt/adminer-updater/update-adminer.php /var/www/html/adminer.php
```

Use `-v` or `--verbose` to enable progress output. Errors go to stderr. The updater exits with status `0` on success and `1` on failure.

## Contributing

See [AGENTS.md](AGENTS.md) for development and testing instructions.
