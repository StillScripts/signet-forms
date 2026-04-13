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
        Schema::table('submissions', function (Blueprint $table) {
            $table->string('status')->default('pending')->after('data');
            $table->json('metadata')->nullable()->after('status');
            $table->unsignedInteger('form_version')->nullable()->after('metadata');
            $table->foreignUuid('assigned_reviewer_id')->nullable()->after('form_version')->constrained('users')->nullOnDelete();
            $table->string('respondent_email')->nullable()->after('assigned_reviewer_id');
            $table->string('respondent_name')->nullable()->after('respondent_email');

            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('submissions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('assigned_reviewer_id');
            $table->dropIndex(['status']);
            $table->dropColumn(['status', 'metadata', 'form_version', 'respondent_email', 'respondent_name']);
        });
    }
};
