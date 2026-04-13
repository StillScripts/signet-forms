<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('forms', function (Blueprint $table) {
            $table->renameColumn('fields', 'schema');
        });

        Schema::table('form_versions', function (Blueprint $table) {
            $table->renameColumn('fields', 'schema');
        });

        $this->migrateFormsToPageStructure();
        $this->migrateFormVersionsToPageStructure();
    }

    public function down(): void
    {
        $this->revertFormVersionsFromPageStructure();
        $this->revertFormsFromPageStructure();

        Schema::table('form_versions', function (Blueprint $table) {
            $table->renameColumn('schema', 'fields');
        });

        Schema::table('forms', function (Blueprint $table) {
            $table->renameColumn('schema', 'fields');
        });
    }

    private function migrateFormsToPageStructure(): void
    {
        DB::table('forms')
            ->whereNotNull('schema')
            ->where('schema', '!=', 'null')
            ->orderBy('id')
            ->each(function (object $form) {
                $data = json_decode($form->schema, true);

                if (! is_array($data) || isset($data['pages'])) {
                    return;
                }

                $schema = [
                    'pages' => [
                        [
                            'id' => Str::uuid()->toString(),
                            'title' => null,
                            'heading' => null,
                            'subheading' => null,
                            'submit_button_text' => null,
                            'fields' => $data,
                        ],
                    ],
                ];

                DB::table('forms')
                    ->where('id', $form->id)
                    ->update(['schema' => json_encode($schema)]);
            });
    }

    private function migrateFormVersionsToPageStructure(): void
    {
        DB::table('form_versions')
            ->whereNotNull('schema')
            ->where('schema', '!=', 'null')
            ->orderBy('id')
            ->each(function (object $version) {
                $data = json_decode($version->schema, true);

                if (! is_array($data) || isset($data['pages'])) {
                    return;
                }

                $schema = [
                    'pages' => [
                        [
                            'id' => Str::uuid()->toString(),
                            'title' => null,
                            'heading' => null,
                            'subheading' => null,
                            'submit_button_text' => null,
                            'fields' => $data,
                        ],
                    ],
                ];

                DB::table('form_versions')
                    ->where('id', $version->id)
                    ->update(['schema' => json_encode($schema)]);
            });
    }

    private function revertFormsFromPageStructure(): void
    {
        DB::table('forms')
            ->whereNotNull('schema')
            ->where('schema', '!=', 'null')
            ->orderBy('id')
            ->each(function (object $form) {
                $data = json_decode($form->schema, true);

                if (! is_array($data) || ! isset($data['pages'])) {
                    return;
                }

                $fields = $data['pages'][0]['fields'] ?? [];

                DB::table('forms')
                    ->where('id', $form->id)
                    ->update(['schema' => json_encode($fields)]);
            });
    }

    private function revertFormVersionsFromPageStructure(): void
    {
        DB::table('form_versions')
            ->whereNotNull('schema')
            ->where('schema', '!=', 'null')
            ->orderBy('id')
            ->each(function (object $version) {
                $data = json_decode($version->schema, true);

                if (! is_array($data) || ! isset($data['pages'])) {
                    return;
                }

                $fields = $data['pages'][0]['fields'] ?? [];

                DB::table('form_versions')
                    ->where('id', $version->id)
                    ->update(['schema' => json_encode($fields)]);
            });
    }
};
