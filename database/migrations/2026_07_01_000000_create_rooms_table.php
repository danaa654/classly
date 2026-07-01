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
        Schema::create('rooms', function (Blueprint $table) {

            $table->id();

            $table->string('room_code')->unique();

            $table->string('room_name');

            $table->enum('room_type', [
                'Lecture',
                'Computer Laboratory',
                'Science Laboratory',
                'Speech Laboratory',
                'PE Area',
                'Any',
            ]);

            $table->string('building');

            $table->string('floor')->nullable();

            $table->unsignedInteger('capacity');

            $table->boolean('active')->default(true);

            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};