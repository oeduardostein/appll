<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('login_security_challenge', 80)->nullable()->after('remember_token');
            $table->string('login_security_key_hash', 64)->nullable()->after('login_security_challenge');
            $table->timestamp('login_security_key_expires_at')->nullable()->after('login_security_key_hash');
            $table->timestamp('login_security_key_sent_at')->nullable()->after('login_security_key_expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'login_security_challenge',
                'login_security_key_hash',
                'login_security_key_expires_at',
                'login_security_key_sent_at',
            ]);
        });
    }
};

