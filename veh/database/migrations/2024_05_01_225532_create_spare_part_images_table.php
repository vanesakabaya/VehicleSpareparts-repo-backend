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
        Schema::create('spare_part_images', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('spare_part_id');
            $table->string('image_url');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable()->useCurrentOnUpdate();
            $table->foreignId('created_by')->constrained('users');

            // Define foreign key constraint
            $table->foreign('spare_part_id')->references('id')->on('spare_parts');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('spare_part_images');
    }
};
