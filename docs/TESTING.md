# Version 3 validation — 2026-10-10

Target: Nextcloud 34.0.1 on Ubuntu 22.04, PHP 8.2.28; browser automation in isolated Chromium.

## Completed

- All PHP app files lint clean. 31 PHP policy assertions and 8 JavaScript policy assertions pass.
- v2 configuration migrates into ordered v3 rules, preserving the original general 300 MiB policy and longest-extension precedence for migrated overrides.
- Pure tests cover group membership, direct user inclusion, exclusion overriding both, anonymous audience, inactive rules, first-match ordering, fallback after exclusion, no matching rule, exact boundary, blocked empty files, placeholders, malformed settings, and removal of principal IDs from effective policy.
- Actual administrator editor opens, searches real Nextcloud users/groups, selects group + individual + excluded individual, creates and saves a scoped rule, edits it, changes priority, disables/enables and deletes it. Changes survive page navigation.
- Through the actual form: an excluded administrator's test upload succeeds (201); removing the exclusion causes the same 2 KB upload against a 0.001 MiB rule to fail (413) with the configured Persian message.
- Three temporary local users and a temporary group exercise real DAV group targeting, direct targeting and exclusion. Group and direct users receive 413 with the custom message; excluded user succeeds. Exactly-at-limit and unrelated extension uploads succeed. Removing membership immediately removes the group rule.
- Chunk assembly rejects actual stored size above limit despite a false OC-Total-Length header. Temporary upload collections and files are deleted.
- A real non-administrator Chromium session receives its effective policy (200), but both directory enumeration and settings changes return 403 even with a valid session CSRF token. The temporary account and isolated browser context are removed.
- Anonymous policy response does not expose targeted rules or principal IDs. Anonymous directory enumeration is denied.
- Mixed browser batch uploads only the permitted small file and displays the custom Persian message for the rejected file. Native drop displays the same message before any upload request for the rejected file. The accepted test file is removed.
- UI test rule, temporary accounts and group are removed. Original general 300 MiB rule remains enabled.

## Scope and previous validation

Version 2 already exercised legacy DAV, unknown Content-Length streams, V1/V2 chunk uploads at boundary and over limit, nested drop trees, picker and folder-row drop in the original validation environment. Version 3 retains those upload hooks and changes their policy selection to use the authenticated uploader's effective rules.

Public upload is disabled in the original validation environment, so end-to-end anonymous public-share upload remains untested. Public read-only policy behavior is tested. Firefox, external object storage, third-party upload apps and future Nextcloud major versions are not certified by these tests. Direct filesystem writes and server-side file creation outside DAV are outside this app's scope.

Unsigned app bundle; not published to the Nextcloud App Store.


This is a historical validation report, not a claim that live integration tests run in GitHub Actions. CI runs the portable policy tests, syntax checks and packaging checks.
