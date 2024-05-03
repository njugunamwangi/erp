<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BrandResource\Pages;
use App\Models\Brand;
use Awcodes\Curator\Components\Forms\CuratorPicker;
use Awcodes\Curator\Components\Tables\CuratorColumn;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BrandResource extends Resource
{
    protected static ?string $model = Brand::class;

    protected static ?string $navigationGroup = 'Commerce';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                CuratorPicker::make('media_id')
                    ->relationship('media', 'name')
                    ->label('Image')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('brand')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('website')
                    ->required()
                    ->prefix('https://')
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                CuratorColumn::make('media_id')
                    ->height(50)
                    ->label('Image'),
                Tables\Columns\TextColumn::make('brand')
                    ->searchable(),
                Tables\Columns\TextColumn::make('website')
                    ->searchable(),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBrands::route('/'),
            'create' => Pages\CreateBrand::route('/create'),
            'view' => Pages\ViewBrand::route('/{record}'),
            'edit' => Pages\EditBrand::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
