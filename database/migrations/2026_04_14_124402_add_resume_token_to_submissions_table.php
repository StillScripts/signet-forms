<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('submissions', function (Blueprint $table) {
            $table->string('resume_token', 64)->nullable()->unique()->after('respondent_name');
            $table->timestamp('resume_token_expires_at')->nullable()->after('resume_token');
            $table->unsignedInteger('resume_page_index')->nullable()->after('resume_token_expires_at');
            $table->boolean('is_draft')->default(false)->after('resume_page_index');

            $table->index('is_draft');
        });
    }

    public function down(): void
    {
        Schema::table('submissions', function (Blueprint $table) {
            $table->dropIndex(['is_draft']);
            $table->dropUnique(['resume_token']);
            $table->dropColumn(['resume_token', 'resume_token_expires_at', 'resume_page_index', 'is_draft']);
        });
    }
};
