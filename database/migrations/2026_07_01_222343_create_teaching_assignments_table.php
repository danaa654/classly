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
        Schema::create('teaching_assignments', function (Blueprint $table) {

            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Academic Term / Section / Curriculum Item / Faculty
            |--------------------------------------------------------------------------
            |
            | MySQL's identifier limit is 64 characters. Laravel's default
            | constraint name pattern is
            | "{table}_{column}_foreign" (and "{table}_{col1}_{col2}_..._unique"
            | for composite indexes below). On this table that default
            | pattern pushes past the limit once combined with the app's
            | naming conventions, which is what throws "Identifier name is
            | too long". Passing a short, explicit indexName to constrained()
            | keeps Laravel's table-inference (faculty_id -> faculty,
            | section_id -> sections, etc.) while giving the constraint
            | itself a short hand-picked name.
            |
            */

            $table->foreignId('academic_term_id')
                ->constrained(indexName: 'ta_academic_term_fk')
                ->cascadeOnDelete();

            $table->foreignId('section_id')
                ->constrained(indexName: 'ta_section_fk')
                ->cascadeOnDelete();

            $table->foreignId('curriculum_item_id')
                ->constrained(indexName: 'ta_curriculum_item_fk')
                ->cascadeOnDelete();

            $table->foreignId('faculty_id')
                ->constrained(indexName: 'ta_faculty_fk')
                ->cascadeOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Remarks
            |--------------------------------------------------------------------------
            */

            $table->string('remarks')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Status
            |--------------------------------------------------------------------------
            */

            $table->boolean('active')
                ->default(true);

            $table->timestamps();

            /*
            |--------------------------------------------------------------------------
            | Prevent Duplicate Assignments
            |--------------------------------------------------------------------------
            |
            | Only one faculty member may be assigned to a given curriculum
            | item, for a given section, in a given academic term. Named
            | explicitly for the same identifier-length reason as above.
            |
            */

            $table->unique(
                [
                    'academic_term_id',
                    'section_id',
                    'curriculum_item_id',
                ],
                'ta_term_section_item_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teaching_assignments');
    }
};