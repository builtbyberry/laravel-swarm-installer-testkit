# Changelog

All notable changes to `laravel-swarm-installer-testkit` will be documented in this file.

## v0.1.2 — 2026-07-06

Documentation only. Backfilled the `v0.1.1` changelog entry above — that CI-fix
patch shipped before its own changelog line existed. No changes to the shipped
harness; the code is identical to `v0.1.1`.

## v0.1.1 — 2026-07-06

Add the missing `phpunit.xml` so CI can run the test suite. No changes to
the shipped harness.

## v0.1.0 — 2026-07-05

Initial release. Extracted from `builtbyberry/laravel-swarm`'s
`tests/Installer/` harness (`InstallerTestCase`, `InstallerRunResult`,
`DoubleRunResult`) to eliminate duplication across companion packages
(starting with `builtbyberry/laravel-swarm-pulse`). `getPackageProviders()`
is now abstract — every consumer declares its own service provider list.
