<?php
// app/Filament/Resources/CertificateTemplateResource.php

namespace App\Filament\Resources;

use App\Filament\Resources\CertificateTemplateResource\Pages;
use App\Models\CertificateTemplate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CertificateTemplateResource extends Resource
{
    protected static ?string $model = CertificateTemplate::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'Certificates';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Basic Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->label('Template Name'),
                        
                        Forms\Components\Select::make('style')
                            ->required()
                            ->options([
                                'modern' => 'Modern',
                                'classic' => 'Classic',
                                'elegant' => 'Elegant',
                            ])
                            ->label('Template Style'),
                        
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                    ])->columns(3),

                Forms\Components\Section::make('Background Settings')
                    ->schema([
                        Forms\Components\ColorPicker::make('background_color')
                            ->label('Background Color')
                            ->default('#ffffff'),
                        
                        Forms\Components\FileUpload::make('background_image')
                            ->label('Background Image (Optional)')
                            ->image()
                            ->directory('certificate-backgrounds'),
                    ])->columns(2),

                Forms\Components\Section::make('Border Settings')
                    ->description('Configure borders for each rank')
                    ->schema([
                        Forms\Components\Repeater::make('borders')
                            ->schema([
                                Forms\Components\Select::make('rank')
                                    ->options([
                                        'diamond' => 'Diamond',
                                        'gold' => 'Gold',
                                        'silver' => 'Silver',
                                        'bronze' => 'Bronze',
                                    ])
                                    ->required()
                                    ->distinct(),
                                
                                Forms\Components\ColorPicker::make('color')
                                    ->required()
                                    ->default('#000000'),
                                
                                Forms\Components\TextInput::make('width')
                                    ->numeric()
                                    ->required()
                                    ->default(8)
                                    ->suffix('px'),
                                
                                Forms\Components\Select::make('style')
                                    ->options([
                                        'solid' => 'Solid',
                                        'double' => 'Double',
                                        'dashed' => 'Dashed',
                                        'dotted' => 'Dotted',
                                    ])
                                    ->required()
                                    ->default('solid'),
                            ])
                            ->columns(4)
                            ->defaultItems(4)
                            ->columnSpanFull()
                            ->mutateDeformedStateUsing(function ($state) {
                                // Convert to keyed array by rank
                                $keyed = [];
                                foreach ($state as $item) {
                                    $keyed[$item['rank']] = [
                                        'color' => $item['color'],
                                        'width' => $item['width'],
                                        'style' => $item['style'],
                                    ];
                                }
                                return $keyed;
                            })
                            ->mutateRelationshipDataUsing(function (array $data) {
                                // Convert back to array for repeater
                                $array = [];
                                foreach ($data as $rank => $settings) {
                                    $array[] = [
                                        'rank' => $rank,
                                        ...$settings
                                    ];
                                }
                                return $array;
                            }),
                    ]),

                Forms\Components\Section::make('Logo Settings')
                    ->schema([
                        Forms\Components\Select::make('logo_settings.position')
                            ->label('Logo Position')
                            ->options([
                                'top-left' => 'Top Left',
                                'top-center' => 'Top Center',
                                'top-right' => 'Top Right',
                                'bottom-center' => 'Bottom Center',
                            ])
                            ->default('top-center')
                            ->required(),
                        
                        Forms\Components\TextInput::make('logo_settings.size')
                            ->label('Logo Size')
                            ->numeric()
                            ->default(80)
                            ->suffix('px')
                            ->required(),
                    ])->columns(2),

                Forms\Components\Section::make('Text Elements')
                    ->description('Add and configure text elements on the certificate')
                    ->schema([
                        Forms\Components\Repeater::make('elements')
                            ->schema([
                                Forms\Components\TextInput::make('id')
                                    ->required()
                                    ->unique()
                                    ->label('Element ID'),
                                
                                Forms\Components\Textarea::make('content')
                                    ->required()
                                    ->label('Content')
                                    ->hint('Use placeholders: [Organization Name], [Rank], [Score], [License Number], [Certificate Number], [Date], [Path], [Issued By]'),
                                
                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\TextInput::make('x')
                                            ->numeric()
                                            ->required()
                                            ->default(50)
                                            ->suffix('%')
                                            ->label('X Position'),
                                        
                                        Forms\Components\TextInput::make('y')
                                            ->numeric()
                                            ->required()
                                            ->default(50)
                                            ->suffix('%')
                                            ->label('Y Position'),
                                    ]),
                                
                                Forms\Components\Grid::make(3)
                                    ->schema([
                                        Forms\Components\TextInput::make('fontSize')
                                            ->numeric()
                                            ->required()
                                            ->default(16)
                                            ->suffix('px')
                                            ->label('Font Size'),
                                        
                                        Forms\Components\Select::make('fontFamily')
                                            ->options([
                                                'serif' => 'Serif',
                                                'sans-serif' => 'Sans-serif',
                                                'monospace' => 'Monospace',
                                                'cursive' => 'Cursive',
                                            ])
                                            ->default('sans-serif')
                                            ->required()
                                            ->label('Font Family'),
                                        
                                        Forms\Components\ColorPicker::make('color')
                                            ->default('#000000')
                                            ->required()
                                            ->label('Text Color'),
                                    ]),
                                
                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\Select::make('align')
                                            ->options([
                                                'left' => 'Left',
                                                'center' => 'Center',
                                                'right' => 'Right',
                                            ])
                                            ->default('center')
                                            ->required()
                                            ->label('Alignment'),
                                        
                                        Forms\Components\Toggle::make('bold')
                                            ->label('Bold')
                                            ->default(false),
                                    ]),
                            ])
                            ->columns(1)
                            ->defaultItems(1)
                            ->columnSpanFull()
                            ->collapsible(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\BadgeColumn::make('style')
                    ->colors([
                        'primary' => 'modern',
                        'success' => 'classic',
                        'warning' => 'elegant',
                    ]),
                
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active'),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('style')
                    ->options([
                        'modern' => 'Modern',
                        'classic' => 'Classic',
                        'elegant' => 'Elegant',
                    ]),
                
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active')
                    ->boolean(),
            ])
            ->actions([
                Tables\Actions\Action::make('preview')
                    ->icon('heroicon-o-eye')
                    ->url(fn (CertificateTemplate $record) => route('filament.admin.resources.certificate-templates.preview', $record))
                    ->openUrlInNewTab(),
                
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCertificateTemplates::route('/'),
            'create' => Pages\CreateCertificateTemplate::route('/create'),
            'edit' => Pages\EditCertificateTemplate::route('/{record}/edit'),
            'preview' => Pages\PreviewCertificateTemplate::route('/{record}/preview'),
        ];
    }
}