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
        if (Schema::hasTable('group_invite_link') && !Schema::hasTable('group_invite_links')) {
            Schema::rename('group_invite_link', 'group_invite_links');
        }

        if (!Schema::hasTable('group_invite_links')) {
            return;
        }

        Schema::table('group_invite_links', function (Blueprint $table) {
            if (!Schema::hasColumn('group_invite_links', 'role')) {
                $table->enum('role', ['admin', 'member'])->default('member')->after('token');
            }

            if (!Schema::hasColumn('group_invite_links', 'current_uses')) {
                $table->unsignedInteger('current_uses')->default(0)->after('max_uses');
            }

            if (!Schema::hasColumn('group_invite_links', 'active')) {
                $table->boolean('active')->default(true)->index()->after('expires_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('group_invite_links')) {
            return;
        }

        Schema::table('group_invite_links', function (Blueprint $table) {
            if (Schema::hasColumn('group_invite_links', 'active')) {
                $table->dropColumn('active');
            }

            if (Schema::hasColumn('group_invite_links', 'current_uses')) {
                $table->dropColumn('current_uses');
            }

            if (Schema::hasColumn('group_invite_links', 'role')) {
                $table->dropColumn('role');
            }
        });

        if (Schema::hasTable('group_invite_links') && !Schema::hasTable('group_invite_link')) {
            Schema::rename('group_invite_links', 'group_invite_link');
        }
    }
};
