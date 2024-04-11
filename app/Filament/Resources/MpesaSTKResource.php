<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MpesaSTKResource\Pages;
use App\Filament\Resources\MpesaSTKResource\RelationManagers;
use App\Models\MpesaSTK;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Iankumu\Mpesa\Facades\Mpesa;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class MpesaSTKResource extends Resource
{
    protected static ?string $model = MpesaSTK::class;
    protected static ?string $navigationGroup = 'Accounting & Finance';
    protected static ?string $navigationLabel = 'STK Push';
    protected static ?string $slug = 'stk-push';
    protected static ?string $modelLabel = 'STK Push';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('result_desc')
                    ->maxLength(255),
                Forms\Components\TextInput::make('result_code')
                    ->maxLength(255),
                Forms\Components\TextInput::make('merchant_request_id')
                    ->maxLength(255),
                Forms\Components\TextInput::make('checkout_request_id')
                    ->maxLength(255),
                Forms\Components\TextInput::make('amount')
                    ->maxLength(255),
                Forms\Components\TextInput::make('mpesa_receipt_number')
                    ->maxLength(255),
                Forms\Components\TextInput::make('transaction_date')
                    ->maxLength(255),
                Forms\Components\TextInput::make('phonenumber')
                    ->tel()
                    ->maxLength(255),
                Forms\Components\TextInput::make('invoice_id')
                    ->numeric(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('invoice.serial')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('result_desc')
                    ->searchable(),
                Tables\Columns\TextColumn::make('result_code')
                    ->searchable(),
                Tables\Columns\TextColumn::make('merchant_request_id')
                    ->searchable(),
                Tables\Columns\TextColumn::make('checkout_request_id')
                    ->searchable(),
                Tables\Columns\TextColumn::make('amount')
                    ->searchable(),
                Tables\Columns\TextColumn::make('mpesa_receipt_number')
                    ->searchable(),
                Tables\Columns\TextColumn::make('transaction_date')
                    ->searchable(),
                Tables\Columns\TextColumn::make('phonenumber')
                    ->searchable(),
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
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Action::make('status')
                    ->label('Check Status')
                    ->icon('heroicon-o-clock')
                    ->color('warning')
                    ->action(function($record) {
                        $response = Mpesa::stkquery($record->checkout_request_id);

                        $result = json_decode((string)$response);
                        dd($result);
                    })
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListMpesaSTKS::route('/'),
            'create' => Pages\CreateMpesaSTK::route('/create'),
            'view' => Pages\ViewMpesaSTK::route('/{record}'),
            'edit' => Pages\EditMpesaSTK::route('/{record}/edit'),
        ];
    }
}
