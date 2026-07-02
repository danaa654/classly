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
                'Laboratory',
            ]);

            /*
            |--------------------------------------------------------------------------
            | Room Group
            |--------------------------------------------------------------------------
            |
            | Mirrors Subject::required_room_group. Names the academic program
            | whose lecture rooms / laboratories this room belongs to (General,
            | BSIT, BSED, BSHM, BSTM, BSCRIM). Criminalistics specializations
            | (FB / LD / QD / FI) collapse to BSCRIM — the scheduler picks
            | whichever Criminalistics room is free.
            |
            | "General" is a Lecture-only value — Laboratory rooms must always
            | belong to a specific program.
            |
            */

            $table->enum('room_group', [
                'General',
                'BSIT',
                'BSED',
                'BSHM',
                'BSTM',
                'BSCRIM',
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