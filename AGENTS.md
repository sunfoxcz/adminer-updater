# Repository Guidelines

## Project Structure

`update-adminer.php` contains the CLI entry point, version detection, download, and file replacement. User-facing setup and operation are documented in [README.md](README.md).

## Development Commands

From the project root, check PHP syntax with Docker. The `sunfoxcz/php:cli` image currently runs PHP 8.6.

```sh
docker run --rm --volume "$PWD:/app:ro" --workdir /app \
  --entrypoint php sunfoxcz/php:cli -l update-adminer.php
```

## Coding Style

Keep the updater compatible with the minimum PHP version listed in the README. Follow the script's four-space indentation, strict types, and explicit parameter and return types. Use camelCase for functions and variables, and uppercase snake case for constants. Use `info()` for verbose progress and `error()` for error messages.

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
