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
        Schema::create('curriculum_subjects', function (Blueprint $table) {

            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Curriculum
            |--------------------------------------------------------------------------
            */

            $table->foreignId('curriculum_id')
                ->constrained()
                ->cascadeOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Subject
            |--------------------------------------------------------------------------
            |
            | References the master subject list. The same subject can be
            | attached to many curriculums (e.g. NSTP1 under BSIT, BSCS,
            | BSHM, etc.) without being duplicated.
            |
            */

            $table->foreignId('subject_id')
                ->constrained()
                ->cascadeOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Curriculum Placement
            |--------------------------------------------------------------------------
            |
            | Where the subject sits within THIS curriculum. The same
            | subject can sit in a different year/semester in another
            | curriculum, which is why this lives on the pivot, not on
            | the subject itself.
            |
            */

            $table->unsignedTinyInteger('year_level');

            // 1 = First Semester
            // 2 = Second Semester
            // 3 = Summer

            $table->unsignedTinyInteger('semester');

            $table->timestamps();

            /*
            |--------------------------------------------------------------------------
            | Prevent Duplicate Assignment
            |--------------------------------------------------------------------------
            |
            | A subject can only be attached to a given curriculum once.
            |
            */

            $table->unique(['curriculum_id', 'subject_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('curriculum_subjects');
    }
};