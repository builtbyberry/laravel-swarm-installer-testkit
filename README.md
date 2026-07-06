# Laravel Swarm Installer Testkit

Shared Testbench-based test harness for `<vendor>:install*` Artisan command
test suites. Extracted from `builtbyberry/laravel-swarm` so companion
packages (`builtbyberry/laravel-swarm-pulse` and others) can `require-dev`
it directly instead of duplicating the harness.

This package is `require-dev`-only. It requires `orchestra/testbench`
directly (rather than through a consumer's own `require-dev`) since testing
Artisan installer commands is this package's entire purpose.

## What you get

`InstallerTestCase` is the base test case. Extend it via a small package
subclass that supplies your service providers:

```php
namespace App\Tests\Installer;

use BuiltByBerry\LaravelSwarmInstallerTestkit\InstallerTestCase;

class MyPackageInstallerTestCase extends InstallerTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            \My\Package\MyPackageServiceProvider::class,
        ];
    }
}
```

Then bind it per test file via the standard Pest `uses()` call:

```php
use App\Tests\Installer\MyPackageInstallerTestCase;

uses(MyPackageInstallerTestCase::class);
```

On `setUp()` the harness:

1. Spins up an Orchestra Testbench application booting the providers your
   subclass declares.
2. Materializes a temp directory shaped like a freshly-scaffolded Laravel 13
   app — `config/`, `routes/console.php`, `app/Providers/AppServiceProvider.php`,
   `.env`, `composer.json`, plus the usual `database/`, `resources/`,
   `storage/`, `bootstrap/`, `public/`, `tests/` directories.
3. Re-points the running application at the scratch skeleton via
   `$this->app->setBasePath(...)` so `app_path()`, `config_path()`,
   `base_path()`, `database_path()`, etc. all resolve into the fixture.
4. Tears the temp directory down in `tearDown()`. Each test gets its own
   uniquely-named skeleton — tests are parallel-safe.

## Writing an installer test

```php
use App\Tests\Installer\MyPackageInstallerTestCase;

uses(MyPackageInstallerTestCase::class);

test('my:install wires up the runtime', function () {
    $this->runInstaller('my:install')
        ->assertSucceeded()
        ->assertOutputContains('Installed.');

    $this->assertFileContains('config/my-package.php', "'driver' => 'database'");
    $this->assertEnvKey('MY_PACKAGE_DRIVER', 'database');
    $this->assertScheduleEntry('my:prune');
});
```

### Idempotency

Every installer should be safe to re-run. The harness has a one-liner:

```php
test('my:install is idempotent', function () {
    $this->runInstaller('my:install')
        ->assertSucceeded()
        ->twice()
        ->assertSecondRunIsNoOp();
});
```

`twice()` runs the installer a second time with the same arguments and
returns a `DoubleRunResult`. `assertSecondRunIsNoOp()` then verifies:

- the second run exited 0
- no file was created
- no file was deleted
- no file's contents (sha256) changed

If your installer needs to be re-runnable but has a `--force` flag that
intentionally overwrites, test `--force` separately — `assertSecondRunIsNoOp()`
is only for the default-mode contract.

### Refusal paths

Every installer should fail loudly when its preconditions are violated
(unsupported driver, already-installed-and-modified, missing `--force`, etc.).
The refusal helper checks both exit code and error message in one call:

```php
test('my:install refuses on an unsupported driver', function () {
    $this->writeSkeletonFile('config/my-package.php', "<?php return ['driver' => 'unsupported'];");

    $this->assertInstallerFailsWith(
        'my:install',
        [],
        'Unsupported driver',
    );
});
```

### Seeding the skeleton

Many installer scenarios need a pre-existing file in the skeleton — _"what
does the installer do when `config/my-package.php` already exists?"_. Use
`writeSkeletonFile()`:

```php
$this->writeSkeletonFile('config/my-package.php', file_get_contents(__DIR__.'/fixtures/old-config.php'));
```

`skeletonPath()` (no args) returns the absolute root, and
`skeletonPath('relative/path')` resolves a relative path inside it.

## Helpers reference

| Helper | Asserts |
|---|---|
| `runInstaller(string $command, array $arguments = []): InstallerRunResult` | Invokes the command. Returns a fluent result. |
| `assertInstallerFailsWith(string $command, array $arguments, string $needle): InstallerRunResult` | Exit code !== 0, output contains the fragment. |
| `assertFileContains(string $relative, string $needle): void` | Skeleton file exists and contains the fragment. |
| `assertEnvKey(string $key, string $value): void` | `.env` defines `KEY=value`. Tolerates quoting. |
| `assertScheduleEntry(string $name): void` | `routes/console.php` registers `Schedule::command('<name>')`. Tolerates whitespace and quote style. |
| `assertProviderBinding(string $abstract, string $concrete): void` | `AppServiceProvider` binds the pair via `bind()` or `singleton()`. Matches both `Foo::class` and `'Foo'` reference forms. |
| `writeSkeletonFile(string $relative, string $contents): void` | Seed a file into the skeleton (parents created as needed). |
| `skeletonPath(string $relative = ''): string` | Resolve an absolute path inside the skeleton. |
| `snapshotSkeleton(): array<string, string>` | Hash every file in the skeleton (relative path => sha256). Used internally by `assertSecondRunIsNoOp()`. |

`InstallerRunResult` also exposes:

- `assertSucceeded(): self` — exit code 0.
- `assertExitCode(int $expected): self` — exit code matches.
- `assertOutputContains(string $needle): self` — console output contains fragment.
- `twice(): DoubleRunResult` — run again, return the pair.
- Public readonly properties: `command`, `arguments`, `exitCode`, `output`,
  `skeletonSnapshot`.

## Registering a command that is not part of your package's default `commands()`

If you are testing an installer that has not yet been wired into your
service provider's `commands()` method, register it manually:

```php
beforeEach(function () {
    $this->registerInstallerCommand(\App\Console\Commands\MyInstallerCommand::class);
});
```

Once the installer ships in your package's default command list, the
`registerInstallerCommand()` call can be deleted.

## Overriding the test environment

`defineEnvironment()` ships a minimal default (in-memory sqlite, array
cache) sufficient for installers that only assert on disk state. Override it
in your subclass if your installer needs a connection that survives across
PDO instances (queue-worker fakes, subprocess assertions) or other
package-specific config.

## Why this is a separate package

The harness extends `Orchestra\Testbench\TestCase`, and `orchestra/testbench`
is a heavy testing framework that has no business in a production
`composer.lock`. Shipping the harness inside a runtime package's own `src/`
would force either promoting testbench to a production `require` (bloating
every consumer's install) or shipping a class with an undeclared dependency.
A standalone `require-dev`-only package sidesteps both.
