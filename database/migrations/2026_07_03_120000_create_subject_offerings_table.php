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
        Schema::create('subject_offerings', function (Blueprint $table) {

            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Academic Term
            |--------------------------------------------------------------------------
            |
            | Every offering belongs to exactly one Academic Term — this is
            | what "Generate Subject Offerings" is scoped to. Cascade on
            | delete: an Academic Term should generally be Archived rather
            | than deleted once it has offerings (see AcademicTerm::
            | hasSchedulingData()), but if it ever is force-deleted, its
            | generated offerings go with it rather than being orphaned.
            */

            $table->foreignId('academic_term_id')
                ->constrained()
                ->cascadeOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Curriculum / Curriculum Item / Subject
            |--------------------------------------------------------------------------
            |
            | curriculum_id and subject_id are denormalized copies of what
            | curriculum_item_id already implies — kept as real columns
            | (rather than reached through the relation every time) because
            | the Index page's Program filter and the EDP Code generator
            | both need to query/group by them directly and cheaply.
            |
            | restrictOnDelete on curriculum_id/subject_id: a Curriculum or
            | Subject that already has generated offerings sitting against
            | it should never be silently deletable out from under them.
            | curriculum_item_id cascades — if a specific item is removed
            | from a curriculum's prospectus, any offering generated from
            | it no longer has a placement to point to.
            */

            $table->foreignId('curriculum_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('curriculum_item_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('subject_id')
                ->constrained()
                ->restrictOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Section
            |--------------------------------------------------------------------------
            |
            | The Section this class is being offered to. Cascade on
            | delete mirrors curriculum_item_id — a deleted Section takes
            | its generated offerings with it.
            */

            $table->foreignId('section_id')
                ->constrained()
                ->cascadeOnDelete();

            /*
            |--------------------------------------------------------------------------
            | EDP Code
            |--------------------------------------------------------------------------
            |
            | System-generated, never entered manually. Format:
            |   PREFIX-YYSYNNN  (e.g. IT-2611001)
            | See EdpCodeService for the generation rules.
            */

            $table->string('edp_code', 20)->unique();

            /*
            |--------------------------------------------------------------------------
            | Placement (denormalized from the Curriculum Item)
            |--------------------------------------------------------------------------
            */

            $table->unsignedTinyInteger('year_level');

            // 1 = First Semester, 2 = Second Semester, 3 = Summer
            $table->unsignedTinyInteger('semester');

            /*
            |--------------------------------------------------------------------------
            | Faculty / Room (assigned later — never at generation time)
            |--------------------------------------------------------------------------
            |
            | Both nullable and intentionally left empty by the generator.
            | Faculty Loading / Scheduling modules are what populate these
            | going forward — this module only ever writes null here.
            */

            $table->foreignId('faculty_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('room_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Status
            |--------------------------------------------------------------------------
            |
            | Every offering is generated as Pending. Confirmed/Cancelled
            | exist as the two other states a future Faculty Loading /
            | Scheduling module will transition an offering through —
            | nothing in this module ever sets anything but Pending.
            */

            $table->enum('status', ['Pending', 'Confirmed', 'Cancelled'])
                ->default('Pending');

            /*
            |--------------------------------------------------------------------------
            | Generated By
            |--------------------------------------------------------------------------
            */

            $table->foreignId('created_by')
                ->constrained('users')
                ->restrictOnDelete();

            $table->timestamps();

            /*
            |--------------------------------------------------------------------------
            | Prevent Duplicate Generation
            |--------------------------------------------------------------------------
            |
            | A given Curriculum Item can only ever produce one offering
            | for a given Section. Re-generating for an Academic Term that
            | already has offerings is handled at the application layer
            | (replace-or-cancel prompt) — this index is the DB-level
            | backstop against that ever producing duplicate rows.
            */

            $table->unique(
                ['section_id', 'curriculum_item_id'],
                'subject_offerings_section_item_unique'
            );

            /*
            |--------------------------------------------------------------------------
            | Lookup Indexes
            |--------------------------------------------------------------------------
            |
            | Every read path (Index filters, EDP sequence lookups) filters
            | by these column groups.
            */

            $table->index(['academic_term_id', 'status']);
            $table->index(['curriculum_id', 'year_level', 'semester']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subject_offerings');
    }
};