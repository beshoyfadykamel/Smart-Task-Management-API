<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('group_invite_link', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->index()->constrained('groups')->cascadeOnDelete();
            $table->foreignId('created_by')->index()->constrained('users')->cascadeOnDelete();
            $table->string('token')->unique();
            $table->integer('max_uses')->nullable();
            $table->dateTime('expires_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('group_invite_link');
    }
};
