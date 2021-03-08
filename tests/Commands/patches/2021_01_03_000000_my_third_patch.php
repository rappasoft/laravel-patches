<?php

use Rappasoft\LaravelPatches\Patch;

class MyThirdPatch extends Patch
{
    /**
     * Run the patch.
     *
     * @return void
     */
    public function up()
    {
        $this->log('Hello Third!');
    }

    /**
     * Reverse the patch.
     *
     * @return void
     */
    public function down()
    {
        \Log::info('Goodbye Third');
    }
}
