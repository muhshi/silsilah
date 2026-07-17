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
        Schema::create('tree_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('family_tree_id')->constrained()->cascadeOnDelete();
            $table->string('email');
            $table->string('role')->default('editor');
            $table->string('token')->unique();
            $table->string('status')->default('pending'); // pending, accepted
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tree_invitations');
    }
};
