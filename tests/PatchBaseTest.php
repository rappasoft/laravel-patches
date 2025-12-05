<?php

namespace Rappasoft\LaravelPatches\Tests;

use Rappasoft\LaravelPatches\Patch;

class PatchBaseTest extends TestCase
{
    public function test_patch_can_have_description_method(): void
    {
        $patch = new class extends Patch {
            public function description(): ?string
            {
                return 'Updates user records';
            }
        };

        $this->assertEquals('Updates user records', $patch->description());
    }

    public function test_patch_description_is_optional_and_defaults_to_null(): void
    {
        $patch = new class extends Patch {};

        $this->assertNull($patch->description());
    }

    public function test_patch_can_have_multi_line_description(): void
    {
        $patch = new class extends Patch {
            public function description(): ?string
            {
                return "This patch performs several operations:\n" .
                       "1. Updates user emails\n" .
                       "2. Migrates legacy data\n" .
                       "3. Cleans up orphaned records";
            }
        };

        $description = $patch->description();
        
        $this->assertStringContainsString('Updates user emails', $description);
        $this->assertStringContainsString('Migrates legacy data', $description);
        $this->assertStringContainsString('Cleans up orphaned records', $description);
    }

    public function test_patch_description_can_include_special_characters(): void
    {
        $patch = new class extends Patch {
            public function description(): ?string
            {
                return 'Fix "quoted" text & special <characters>';
            }
        };

        $this->assertStringContainsString('"quoted"', $patch->description());
        $this->assertStringContainsString('&', $patch->description());
        $this->assertStringContainsString('<characters>', $patch->description());
    }

    public function test_patch_description_can_be_dynamically_generated(): void
    {
        $patch = new class extends Patch {
            private int $recordCount = 100;
            
            public function description(): ?string
            {
                return "Processes {$this->recordCount} records";
            }
        };

        $this->assertEquals('Processes 100 records', $patch->description());
    }

    public function test_patch_description_method_returns_string_or_null(): void
    {
        $stringPatch = new class extends Patch {
            public function description(): ?string
            {
                return 'Description';
            }
        };

        $nullPatch = new class extends Patch {
            public function description(): ?string
            {
                return null;
            }
        };

        $this->assertIsString($stringPatch->description());
        $this->assertNull($nullPatch->description());
    }

    public function test_patch_with_empty_string_description(): void
    {
        $patch = new class extends Patch {
            public function description(): ?string
            {
                return '';
            }
        };

        $this->assertEquals('', $patch->description());
    }

    public function test_patch_can_set_useTransaction_to_true(): void
    {
        $patch = new class extends Patch {
            protected bool $useTransaction = true;
        };

        $reflection = new \ReflectionClass($patch);
        $property = $reflection->getProperty('useTransaction');
        $property->setAccessible(true);

        $this->assertTrue($property->getValue($patch));
    }

    public function test_patch_can_override_useTransaction_per_instance(): void
    {
        $patchWithTransaction = new class extends Patch {
            protected bool $useTransaction = true;
        };

        $patchWithoutTransaction = new class extends Patch {
            protected bool $useTransaction = false;
        };

        $reflection1 = new \ReflectionClass($patchWithTransaction);
        $property1 = $reflection1->getProperty('useTransaction');
        $property1->setAccessible(true);

        $reflection2 = new \ReflectionClass($patchWithoutTransaction);
        $property2 = $reflection2->getProperty('useTransaction');
        $property2->setAccessible(true);

        $this->assertTrue($property1->getValue($patchWithTransaction));
        $this->assertFalse($property2->getValue($patchWithoutTransaction));
    }
}
