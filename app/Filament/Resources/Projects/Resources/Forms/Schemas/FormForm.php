<?php

namespace App\Filament\Resources\Projects\Resources\Forms\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class FormForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Textarea::make('description')
                    ->maxLength(1000)
                    ->rows(3),
                Toggle::make('is_published')
                    ->label('Published')
                    ->helperText('Published forms can accept submissions.')
                    ->hiddenOn('create'),
                Section::make('Settings')
                    ->description('Configure form behaviour, confirmation messages, and more.')
                    ->schema([
                        Tabs::make('settings')
                            ->tabs([
                                Tab::make('Confirmation')
                                    ->icon('heroicon-o-check-circle')
                                    ->schema([
                                        TextInput::make('settings.confirmation.heading')
                                            ->label('Heading')
                                            ->placeholder('Thank you!')
                                            ->maxLength(255),
                                        Textarea::make('settings.confirmation.message')
                                            ->label('Message')
                                            ->placeholder('Your response has been recorded.')
                                            ->rows(2)
                                            ->maxLength(1000),
                                        TextInput::make('settings.confirmation.redirect_url')
                                            ->label('Redirect URL')
                                            ->helperText('Redirect respondents to this URL after submission instead of showing the confirmation message.')
                                            ->url()
                                            ->maxLength(2048),
                                    ]),
                                Tab::make('Behaviour')
                                    ->icon('heroicon-o-cog-6-tooth')
                                    ->schema([
                                        TextInput::make('settings.behaviour.submission_limit')
                                            ->label('Submission limit')
                                            ->helperText('Maximum number of submissions this form will accept. Leave empty for unlimited.')
                                            ->numeric()
                                            ->minValue(1),
                                        DateTimePicker::make('settings.behaviour.close_date')
                                            ->label('Close date')
                                            ->helperText('Stop accepting submissions after this date.'),
                                        Toggle::make('settings.behaviour.allow_multiple_submissions')
                                            ->label('Allow multiple submissions')
                                            ->helperText('Allow the same person to submit more than once.'),
                                    ]),
                                Tab::make('Compliance')
                                    ->icon('heroicon-o-shield-check')
                                    ->schema([
                                        TextInput::make('settings.compliance.retention_days')
                                            ->label('Data retention (days)')
                                            ->helperText('Automatically delete submissions after this many days. Leave empty to retain indefinitely.')
                                            ->numeric()
                                            ->minValue(1),
                                        TextInput::make('settings.compliance.data_residency')
                                            ->label('Data residency')
                                            ->helperText('Preferred data storage region.')
                                            ->maxLength(255),
                                        Textarea::make('settings.compliance.consent_text')
                                            ->label('Consent text')
                                            ->helperText('Consent message displayed to respondents before submission.')
                                            ->rows(2)
                                            ->maxLength(2000),
                                    ]),
                                Tab::make('Branding')
                                    ->icon('heroicon-o-paint-brush')
                                    ->schema([
                                        TextInput::make('settings.branding.primary_colour')
                                            ->label('Primary colour')
                                            ->helperText('Hex colour code (e.g. #3B82F6).')
                                            ->maxLength(7),
                                        TextInput::make('settings.branding.font_family')
                                            ->label('Font family')
                                            ->helperText('Custom font family name.')
                                            ->maxLength(255),
                                        Textarea::make('settings.branding.custom_css')
                                            ->label('Custom CSS')
                                            ->helperText('Custom CSS applied to the public form. Use with caution.')
                                            ->rows(3)
                                            ->maxLength(5000),
                                    ]),
                                Tab::make('Embed')
                                    ->icon('heroicon-o-code-bracket')
                                    ->schema([
                                        Toggle::make('settings.embed.allow_embedding')
                                            ->label('Allow embedding')
                                            ->helperText('Allow this form to be embedded in other websites via an iframe.')
                                            ->default(true),
                                        TextInput::make('settings.embed.width')
                                            ->label('Width')
                                            ->helperText('Width of the embedded iframe (e.g. 100%, 600px).')
                                            ->default('100%')
                                            ->maxLength(50),
                                        TextInput::make('settings.embed.height')
                                            ->label('Height (pixels)')
                                            ->helperText('Height of the embedded iframe in pixels.')
                                            ->numeric()
                                            ->minValue(100)
                                            ->maxValue(5000)
                                            ->default(600),
                                    ]),
                            ]),
                    ])
                    ->collapsed()
                    ->hiddenOn('create'),
            ]);
    }
}
