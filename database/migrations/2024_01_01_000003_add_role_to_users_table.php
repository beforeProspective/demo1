<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['user', 'admin'])->default('user')->after('email');
            $table->enum('status', ['active', 'inactive', 'banned'])->default('active')->after('role');
        });

        Schema::create('jwt_blacklist', function (Blueprint $table) {
            $table->id();
            $table->string('token_id')->unique();
            $table->string('token', 500);
            $table->timestamp('expires_at');
            $table->timestamp('blacklisted_at')->useCurrent();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
            $table->index(['token_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'status']);
        });
        Schema::dropIfExists('jwt_blacklist');
    }
};