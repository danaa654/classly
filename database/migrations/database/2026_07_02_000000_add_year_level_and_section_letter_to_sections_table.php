<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Section Codes are now system-generated from Program + (optionally)
     * Specialization + Year Level + Section Letter, instead of being
     * typed in free-hand. year_level and section_letter are the two
     * pieces that weren't captured anywhere before — everything else
     * (Program, Specialization) is already reachable through the
     * existing curriculum_id relationship, so no other columns are
     * needed.
     */
    public function up(): void
    {
        Schema::table('sections', function (Blueprint $table) {

            $table->unsignedTinyInteger('year_level')
                ->nullable()
                ->after('section_name');

            $table->char('section_letter', 1)
                ->nullable()
                ->after('year_level');

        });

        /*
        |----------------------------------------------------------------
        | Backfill for existing rows
        |----------------------------------------------------------------
        |
        | Best-effort only. Every code this system has ever generated
        | ends in "<digits><letter>" (e.g. BSIT-1A, BSCRIM-FI-4A), so
        | that's what we try to recover. Rows that don't match (e.g. a
        | hand-typed code that didn't follow the convention) are simply
        | left null — SectionController requires year_level and
        | section_letter on update, so the next time that section is
        | edited the user will be asked to pick both, which regenerates
        | a clean, conforming code. Nothing here breaks existing rows;
        | their current section_code is untouched either way.
        |
        */
        DB::table('sections')->orderBy('id')->chunk(100, function ($sections) {
            foreach ($sections as $section) {
                if (preg_match('/(\d+)([A-Za-z])$/', $section->section_code, $matches)) {
                    DB::table('sections')
                        ->where('id', $section->id)
                        ->update([
                            'year_level' => (int) $matches[1],
                            'section_letter' => strtoupper($matches[2]),
                        ]);
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sections', function (Blueprint $table) {
            $table->dropColumn(['year_level', 'section_letter']);
        });
    }
};