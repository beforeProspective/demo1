<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('token_blacklist', function (Blueprint $table) {
            $table->id();
            $table->string('token_jti', 64)->unique()->comment('JWT ID');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamp('expires_at')->comment('Token expiration time');
            $table->string('reason')->default('logout')->comment('Reason for blacklisting');
            $table->timestamps();

            $table->index(['token_jti', 'expires_at']);
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('token_blacklist');
    }
};
