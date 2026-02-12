<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('placas_zero_km_batches', function (Blueprint $table) {
            $table->id();
            $table->string('status', 30)->default('pending'); // pending|running|completed
            $table->unsignedInteger('total')->default(0);
            $table->unsignedInteger('processed')->default(0);
            $table->unsignedInteger('succeeded')->default(0);
            $table->unsignedInteger('failed')->default(0);
            $table->string('source', 30)->default('public_web');
            $table->string('request_ip', 64)->nullable();
            $table->timestamps();
        });

        Schema::create('placas_zero_km_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained('placas_zero_km_batches')->cascadeOnDelete();
            $table->string('cpf_cgc', 20);
            $table->string('nome', 150)->nullable();
            $table->string('chassi', 32);
            $table->string('numeros', 8)->nullable();
            $table->string('status', 30)->default('pending'); // pending|running|succeeded|failed
            $table->unsignedInteger('attempts')->default(0);
            $table->json('response_payload')->nullable();
            $table->text('response_error')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'id']);
            $table->index(['batch_id', 'status']);
        });

        Schema::create('placas_zero_km_runner_state', function (Blueprint $table) {
            $table->unsignedTinyInteger('id')->primary();
            $table->unsignedBigInteger('current_request_id')->nullable();
            $table->unsignedTinyInteger('is_running')->default(0);
            $table->timestamp('last_heartbeat_at')->nullable();
            $table->timestamps();
        });

        DB::table('placas_zero_km_runner_state')->updateOrInsert(
            ['id' => 1],
            [
                'current_request_id' => null,
                'is_running' => 0,
                'last_heartbeat_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('placas_zero_km_runner_state');
        Schema::dropIfExists('placas_zero_km_requests');
        Schema::dropIfExists('placas_zero_km_batches');
    }
};

