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
        Schema::table('members', function (Blueprint $table) {
            $table->foreignId('source_tree_id')->nullable()->after('family_tree_id')->constrained('family_trees')->nullOnDelete();
            $table->foreignId('source_member_id')->nullable()->after('source_tree_id')->constrained('members')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropForeign(['source_tree_id']);
            $table->dropForeign(['source_member_id']);
            $table->dropColumn(['source_tree_id', 'source_member_id']);
        });
    }
};
