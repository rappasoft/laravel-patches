<?php

namespace Rappasoft\LaravelPatches\Tests\Commands;

use Illuminate\Support\Facades\File;
use InvalidArgumentException;
use Rappasoft\LaravelPatches\Tests\TestCase;

class PatchMakeTest extends TestCase
{
    /** @test */
    public function it_makes_a_patch_file()
    {
        $this->artisan('make:patch', ['name' => 'new_patch'])->run();

        $this->assertDirectoryExists(database_path('patches'));
        $this->assertEquals(1, collect(File::files(database_path('patches')))->count());
    }

    /** @test */
    public function it_prepopulates_the_patch_with_the_stub_file()
    {
        $this->artisan('make:patch', ['name' => 'new_patch'])->run();

        foreach (glob(database_path('patches').'/*') as $file) {
            $this->assertTrue(filesize($file) > 0);
        }
    }

    /** @test */
    public function it_doesnt_make_two_patches_with_the_same_name()
    {
        $this->expectException(InvalidArgumentException::class);

        $this->artisan('make:patch', ['name' => 'new_patch'])->run();
        $this->artisan('make:patch', ['name' => 'new_patch'])->run();
    }
}
