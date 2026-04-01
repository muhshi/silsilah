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
        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('family_tree_id')->constrained()->cascadeOnDelete();
            
            $table->string('first_name');
            $table->string('last_name')->nullable();
            $table->enum('gender', ['male', 'female']);
            $table->boolean('is_living')->default(true);
            
            $table->date('birth_date')->nullable();
            $table->date('death_date')->nullable();
            $table->string('birth_place')->nullable();
            $table->string('death_place')->nullable();
            
            $table->foreignId('father_id')->nullable()->constrained('members')->nullOnDelete();
            $table->foreignId('mother_id')->nullable()->constrained('members')->nullOnDelete();
            
            $table->string('photo')->nullable();
            $table->string('avatar_id')->nullable();
            
            $table->string('facebook')->nullable();
            $table->string('instagram')->nullable();
            $table->string('whatsapp')->nullable();
            
            $table->text('address')->nullable();
            $table->string('phone_home')->nullable();
            $table->string('profession')->nullable();
            $table->string('company')->nullable();
            $table->text('interests')->nullable();
            $table->text('bio')->nullable();
            
            $table->integer('order')->default(0);
            $table->string('external_family_tree_link')->nullable();
            $table->text('member_notes')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};
