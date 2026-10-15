<?php

namespace Tests;

use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Schema;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (Schema::hasTable('permissions') && \Spatie\Permission\Models\Permission::count() === 0) {
            $this->seed(RolePermissionSeeder::class);
        }
    }
}
