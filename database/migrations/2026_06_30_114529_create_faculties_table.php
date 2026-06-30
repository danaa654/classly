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
        Schema::create('faculties', function (Blueprint $table) {

            $table->id();

            // Personal Information
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->string('suffix')->nullable();

            $table->enum('gender', [
                'Male',
                'Female'
            ])->nullable();

            $table->string('contact_number')->nullable();

            $table->string('email')->unique()->nullable();

            // Home College
            $table->foreignId('department_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            // Employment
            $table->enum('employment_type', [
                'Full-Time',
                'Part-Time',
            ]);

            // Maximum teaching load
            $table->unsignedTinyInteger('max_units')
                ->default(24);

            // Can teach...
            $table->enum('teaching_qualification', [
                'Major',
                'Minor',
                'Both',
            ]);

            // Active / Inactive
            $table->boolean('status')->default(true);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('faculties');
    }
};