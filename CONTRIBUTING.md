# Contributing

Keep pull requests focused and include a concrete before/after example.

Run `python tools/check.py` and `python tools/build.py` before submitting changes. Tests need PHP 8.2+ with mbstring, Node.js 18+, and Python 3.10+. If PHP is unavailable, `python tools/check.py --skip-php` runs the remaining checks but is not a complete test run.

The standalone policy tests do not require a Nextcloud installation. Changes to DAV hooks, authentication, directory search or browser upload interception also require integration testing on a disposable Nextcloud 34 instance. Never run destructive fixtures against a production installation. Do not submit credentials, private server addresses, personal user lists or internal screenshots.

Preserve these semantics: first matching active rule wins; group/user inclusion uses OR; user exclusion skips only that rule; no match means no app restriction; exact boundary is allowed. Both browser and PHP policy implementations must agree.

For a release, update appinfo/info.xml and CHANGELOG.md, run the checks, and build fresh archives. Do not just increase Nextcloud's max-version without compatibility testing. Keep the AGPL license and existing attribution intact.
