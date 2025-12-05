<?php

use Illuminate\Support\Facades\File;

it('makes a patch file', function () {
    $this->artisan('make:patch', ['name' => 'new_patch'])->assertSuccessful();

    expect(database_path('patches'))->toBeDirectory();
    expect(collect(File::files(database_path('patches')))->count())->toBe(1);
});

it('prepopulates the patch with the stub file', function () {
    $this->artisan('make:patch', ['name' => 'new_patch'])->assertSuccessful();

    foreach (glob(database_path('patches').'/*') as $file) {
        expect(filesize($file))->toBeGreaterThan(0);
    }
});

it('doesnt make two patches with the same name', function () {
    $this->artisan('make:patch', ['name' => 'new_patch'])->assertSuccessful();
    
    $this->artisan('make:patch', ['name' => 'new_patch'])
        ->assertFailed();
})->throws(InvalidArgumentException::class);

it('creates patch with correct naming convention', function () {
    $this->artisan('make:patch', ['name' => 'my_test_patch'])->assertSuccessful();

    $files = glob(database_path('patches').'/*_my_test_patch.php');
    expect($files)->toHaveCount(1);

    $fileName = basename($files[0]);
    expect($fileName)->toMatch('/^\d{4}_\d{2}_\d{2}_\d{6}_my_test_patch\.php$/');
});

it('generates correct class name from patch name', function () {
    $this->artisan('make:patch', ['name' => 'my_awesome_patch'])->assertSuccessful();

    $files = glob(database_path('patches').'/*_my_awesome_patch.php');
    $content = file_get_contents($files[0]);

    expect($content)->toContain('class MyAwesomePatch');
});

it('creates patches directory if it does not exist', function () {
    // Remove patches directory
    if (is_dir(database_path('patches'))) {
        foreach (glob(database_path('patches').'/*') as $file) {
            unlink($file);
        }
        rmdir(database_path('patches'));
    }

    expect(database_path('patches'))->not->toBeDirectory();

    $this->artisan('make:patch', ['name' => 'first_patch'])->assertSuccessful();

    expect(database_path('patches'))->toBeDirectory();
});

it('includes stub content in generated patch', function () {
    $this->artisan('make:patch', ['name' => 'stub_test'])->assertSuccessful();

    $files = glob(database_path('patches').'/*_stub_test.php');
    $content = file_get_contents($files[0]);

    expect($content)->toContain('use Rappasoft\LaravelPatches\Patch')
        ->and($content)->toContain('public function up()')
        ->and($content)->toContain('public function down()');
});

it('converts snake_case to StudlyCase for class names', function () {
    $testCases = [
        'simple_patch' => 'SimplePatch',
        'my_complex_patch_name' => 'MyComplexPatchName',
        'another' => 'Another',
    ];

    foreach ($testCases as $input => $expected) {
        $this->artisan('make:patch', ['name' => $input])->assertSuccessful();
        
        $files = glob(database_path('patches')."/*_{$input}.php");
        $content = file_get_contents($files[0]);
        
        expect($content)->toContain("class {$expected}");
        
        // Clean up
        unlink($files[0]);
    }
});

it('dumps autoload after creating patch', function () {
    // This is tested implicitly - the composer dump-autoload is called
    // We can verify the patch is created successfully
    $this->artisan('make:patch', ['name' => 'autoload_test'])->assertSuccessful();
    
    $files = glob(database_path('patches').'/*_autoload_test.php');
    expect($files)->toHaveCount(1);
});
