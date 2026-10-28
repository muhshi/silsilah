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
        Schema::create('family_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('family_tree_id')->constrained('family_trees')->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->string('event_type')->default('pertemuan'); // halal_bi_halal, haul_akbar, arisan, pertemuan, ziarah, lainnya
            $table->date('event_date');
            $table->string('start_time', 20)->nullable();
            $table->string('end_time', 20)->nullable();
            $table->string('location_name')->nullable();
            $table->text('location_maps_url')->nullable();
            $table->text('description')->nullable();
            $table->string('contact_person')->nullable();
            $table->boolean('is_recurring_yearly')->default(false);
            $table->timestamps();

            $table->index(['family_tree_id', 'event_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('family_events');
    }
};
