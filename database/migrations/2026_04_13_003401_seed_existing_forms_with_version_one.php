<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $forms = DB::table('forms')
            ->whereNotNull('fields')
            ->where('fields', '!=', 'null')
            ->get(['id', 'fields', 'created_at']);

        foreach ($forms as $form) {
            DB::table('form_versions')->insert([
                'form_id' => $form->id,
                'version' => 1,
                'fields' => $form->fields,
                'created_at' => $form->created_at,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('form_versions')->where('version', 1)->delete();
    }
};
