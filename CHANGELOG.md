# Changelog

All notable changes to `laravel-swarm-installer-testkit` will be documented in this file.

## v0.1.1 — 2026-07-06

Add the missing `phpunit.xml` so CI can run the test suite. No changes to
the shipped harness.

## v0.1.0 — 2026-07-05

Initial release. Extracted from `builtbyberry/laravel-swarm`'s
`tests/Installer/` harness (`InstallerTestCase`, `InstallerRunResult`,
`DoubleRunResult`) to eliminate duplication across companion packages
(starting with `builtbyberry/laravel-swarm-pulse`). `getPackageProviders()`
is now abstract — every consumer declares its own service provider list.
