<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMetadataToPatchesTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table(config('laravel-patches.table_name'), function (Blueprint $table) {
            $table->integer('execution_time_ms')->nullable()->after('log');
            $table->decimal('memory_used_mb', 8, 2)->nullable()->after('execution_time_ms');
            $table->string('executed_by')->nullable()->after('memory_used_mb');
            $table->string('environment', 50)->nullable()->after('executed_by');
            $table->enum('status', ['success', 'failed', 'rolled_back'])->default('success')->after('environment');
            $table->text('error_message')->nullable()->after('status');
            $table->longText('error_trace')->nullable()->after('error_message');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table(config('laravel-patches.table_name'), function (Blueprint $table) {
            $table->dropColumn([
                'execution_time_ms',
                'memory_used_mb',
                'executed_by',
                'environment',
                'status',
                'error_message',
                'error_trace',
            ]);
        });
    }
}
