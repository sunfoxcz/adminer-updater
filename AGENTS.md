# Repository Guidelines

## Project Structure

`update-adminer.php` is the PHP CLI entry point. It reads the latest version from adminer.org and compares it with the version in the specified Adminer PHP file. When an update is needed, the script downloads the full release from GitHub to a sibling `.tmp` file, backs up the previous installation with a `.bak` suffix, and moves the download into place. Make updater changes in `update-adminer.php`.

## Development Commands

From the project root, check PHP syntax with Docker. The `sunfoxcz/php:cli` image currently runs PHP 8.6.

```sh
docker run --rm --volume "$PWD:/app:ro" --workdir /app \
  --entrypoint php sunfoxcz/php:cli -l update-adminer.php
```

Pass the required file path as a positional argument. Relative paths resolve from the working directory. Use `--` before a path that starts with `-`.

The updater returns `0` on success, including when no update is needed, and `1` on failure. Errors go to stderr. Pass `-v` or `--verbose` to show progress.

## Coding Style

Keep the updater compatible with PHP 7.4. Follow the script's four-space indentation, strict types, and explicit parameter and return types. Use camelCase for functions and variables, and uppercase snake case for constants. Use `info()` for verbose progress and `error()` for error messages.

## Testing

From the project root, copy the updater to a temporary directory and run it in Docker. The test downloads Adminer and writes `test-site/adminer.php` inside that directory.

```sh
test_dir=$(mktemp -d)
cp update-adminer.php "$test_dir/"
docker run --rm --user "$(id -u):$(id -g)" \
  --volume "$test_dir:/app" --workdir /app \
  --entrypoint php sunfoxcz/php:cli update-adminer.php -v test-site/adminer.php
```

Repeat the Docker command to check that an up-to-date installation stays untouched. To test replacement, put an older Adminer release at `$test_dir/test-site/adminer.php`, rerun the command, and check `adminer.php.bak`. For download changes, verify that a failed download leaves the existing installation usable. To check PHP 7.4 compatibility, substitute `sunfoxcz/php:7.4-cli` in the commands.

## Pull Requests

In the pull request, summarize the change and the checks you ran, link the related issue when available, and flag changes to release sources or destination paths.
