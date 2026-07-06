<?php

declare(strict_types=1);

namespace BuiltByBerry\LaravelSwarmInstallerTestkit\Tests\Support;

use BuiltByBerry\LaravelSwarmInstallerTestkit\InstallerTestCase;

/**
 * Minimal concrete subclass used by this package's own self-tests.
 *
 * The fixture commands under `tests/Fixtures/` need no application service
 * providers beyond the framework defaults testbench already boots.
 */
class ConcreteInstallerTestCase extends InstallerTestCase
{
    protected function getPackageProviders($app): array
    {
        return [];
    }
}
