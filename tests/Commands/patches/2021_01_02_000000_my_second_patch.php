<?php

use Rappasoft\LaravelPatches\Patch;

class MySecondPatch extends Patch
{
    /**
     * Run the patch.
     *
     * @return void
     */
    public function up()
    {
        $this->log('Hello Second!');
    }

    /**
     * Reverse the patch.
     *
     * @return void
     */
    public function down()
    {
        \Log::info('Goodbye Second');
    }
}
