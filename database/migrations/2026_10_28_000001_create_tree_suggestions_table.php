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
        Schema::create('tree_suggestions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('family_tree_id')->constrained('family_trees')->cascadeOnDelete();
            $table->foreignId('target_member_id')->nullable()->constrained('members')->nullOnDelete();
            $table->string('type'); // 'add_child', 'add_spouse', 'edit_member'
            $table->json('data')->nullable();
            $table->string('contributor_name');
            $table->string('contributor_contact')->nullable();
            $table->text('notes')->nullable();
            $table->string('status')->default('pending'); // 'pending', 'approved', 'rejected'
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['family_tree_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tree_suggestions');
    }
};
