<?php

namespace App\Filament\Resources\Projects\Resources\Forms;

use App\Filament\Resources\Projects\ProjectResource;
use App\Filament\Resources\Projects\Resources\Forms\Pages\CreateForm;
use App\Filament\Resources\Projects\Resources\Forms\Pages\EditForm;
use App\Filament\Resources\Projects\Resources\Forms\Pages\FormBuilderPage;
use App\Filament\Resources\Projects\Resources\Forms\Pages\ListForms;
use App\Filament\Resources\Projects\Resources\Forms\Pages\ViewForm;
use App\Filament\Resources\Projects\Resources\Forms\Schemas\FormForm;
use App\Filament\Resources\Projects\Resources\Forms\Schemas\FormInfolist;
use App\Filament\Resources\Projects\Resources\Forms\Tables\FormsTable;
use App\Models\Form;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class FormResource extends Resource
{
    protected static ?string $model = Form::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $parentResource = ProjectResource::class;

    protected static bool $isScopedToTenant = false;

    public static function form(Schema $schema): Schema
    {
        return FormForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return FormInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FormsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListForms::route('/'),
            'create' => CreateForm::route('/create'),
            'view' => ViewForm::route('/{record}'),
            'edit' => EditForm::route('/{record}/edit'),
            'builder' => FormBuilderPage::route('/{record}/builder'),
        ];
    }
}
