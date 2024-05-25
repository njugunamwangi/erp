<?php

namespace App\Models;

use App\Enums\AccountStatus;
use App\Enums\AccountType;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Wallo\FilamentSelectify\Components\ToggleButton;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;
use Ysfkaya\FilamentPhoneInput\PhoneInputNumberType;

class Account extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $guarded = [];

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function makeDefault()
    {
        $this->enabled = true;
        $this->save();
    }

    protected function casts(): array
    {
        return [
            'type' => AccountType::class,
            'status' => AccountStatus::class,
        ];
    }

    public static function getForm(): array
    {
        return [
            Group::make()
                ->schema([
                    Section::make('Account Information')
                        ->schema([
                            Grid::make(2)
                                ->schema([
                                    Select::make('type')
                                        ->enum(AccountType::class)
                                        ->options(AccountType::class)
                                        ->required()
                                        ->searchable()
                                        ->preload()
                                        ->default(AccountType::DEFAULT),
                                    TextInput::make('name')
                                        ->required()
                                        ->maxLength(100),
                                ]),
                            Grid::make(2)
                                ->schema([
                                    TextInput::make('number')
                                        ->required()
                                        ->maxLength(20),
                                    ToggleButton::make('enabled')
                                        ->label('Default')
                                        ->offColor('danger')
                                        ->onColor('info')
                                        ->offLabel('No')
                                        ->onLabel('Yes')
                                        ->required(),
                                ]),
                        ]),
                    Section::make('Currency & Status')
                        ->schema([
                            Grid::make(2)
                                ->schema([
                                    Select::make('currency_id')
                                        ->relationship('currency', 'abbr')
                                        ->label('Currency')
                                        ->optionsLimit(40)
                                        ->searchable()
                                        ->createOptionForm(Currency::getForm())
                                        ->editOptionForm(Currency::getForm())
                                        ->live()
                                        ->preload()
                                        ->getSearchResultsUsing(fn (string $search): array => Currency::whereAny([
                                            'name', 'abbr', 'symbol', 'code'], 'like', "%{$search}%")->limit(50)->pluck('abbr', 'id')->toArray())
                                        ->getOptionLabelUsing(fn ($value): ?string => Currency::find($value)?->abbr)
                                        ->loadingMessage('Loading currencies...')
                                        ->searchPrompt('Search currencies by their symbol, abbreviation or country')
                                        ->required(),
                                    Select::make('status')
                                        ->required()
                                        ->enum(AccountStatus::class)
                                        ->options(AccountStatus::class)
                                        ->searchable()
                                        ->preload()
                                        ->default(AccountStatus::DEFAULT),
                                ]),
                        ]),
                    Tabs::make('Account Specifications')
                        ->tabs([
                            Tabs\Tab::make('Bank Information')
                                ->icon('heroicon-o-building-office-2')
                                ->schema([
                                    Grid::make(2)
                                        ->schema([
                                            TextInput::make('bank_name')
                                                ->maxLength(100),
                                            PhoneInput::make('bank_phone')
                                                ->defaultCountry('KE')
                                                ->displayNumberFormat(PhoneInputNumberType::INTERNATIONAL)
                                                ->focusNumberFormat(PhoneInputNumberType::INTERNATIONAL),
                                        ]),
                                    RichEditor::make('bank_address')
                                        ->columnSpanFull(),
                                ]),
                            Tabs\Tab::make('Additional Information')
                                ->icon('heroicon-o-adjustments-horizontal')
                                ->schema([
                                    TextInput::make('description')
                                        ->maxLength(255),
                                    Textarea::make('notes')
                                        ->columnSpanFull(),
                                    TextInput::make('bank_website')
                                        ->prefix('https://')
                                        ->url()
                                        ->maxLength(255),
                                ]),
                        ]),
                ])->columnSpan(8),
            Group::make()
                ->schema([
                    Section::make('International Banking Details')
                        ->schema([
                            TextInput::make('bic_swift_code')
                                ->maxLength(11),
                            TextInput::make('iban')
                                ->maxLength(34),
                        ]),
                    Section::make('Routing Information')
                        ->schema([
                            TextInput::make('aba_routing_number')
                                ->maxLength(9),
                            TextInput::make('ach_routing_number')
                                ->maxLength(9),
                        ]),
                ])->columnSpan(4),
        ];
    }
}
