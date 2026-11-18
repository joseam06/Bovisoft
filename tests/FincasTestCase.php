<?php

namespace Tests;

use Tests\Support\FincasApplication;

abstract class FincasTestCase extends TestCase
{
    public function createApplication()
    {
        $this->traitsUsedByTest = array_flip(class_uses_recursive(static::class));

        return FincasApplication::create();
    }
}
