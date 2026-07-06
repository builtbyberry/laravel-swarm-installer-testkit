# Changelog

All notable changes to `laravel-swarm-installer-testkit` will be documented in this file.

## v0.1.0 — 2026-07-05

Initial release. Extracted from `builtbyberry/laravel-swarm`'s
`tests/Installer/` harness (`InstallerTestCase`, `InstallerRunResult`,
`DoubleRunResult`) to eliminate duplication across companion packages
(starting with `builtbyberry/laravel-swarm-pulse`). `getPackageProviders()`
is now abstract — every consumer declares its own service provider list.
