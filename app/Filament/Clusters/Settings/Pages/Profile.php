<?php

namespace App\Filament\Clusters\Settings\Pages;

use App\Enums\EntityType;
use App\Filament\Clusters\Settings;
use App\Models\Currency;
use App\Models\Profile as ModelsProfile;
use Awcodes\Curator\Components\Forms\CuratorPicker;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Concerns\InteractsWithFormActions;
use Filament\Pages\Page;
use Filament\Support\Exceptions\Halt;
use Livewire\Attributes\Locked;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;
use Ysfkaya\FilamentPhoneInput\PhoneInputNumberType;

class Profile extends Page
{
    use InteractsWithFormActions;
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-user';

    protected static ?string $title = 'Company Profile';

    protected static string $view = 'filament.clusters.settings.pages.profile';

    protected static ?int $navigationSort = 2;

    protected static ?string $cluster = Settings::class;

    public ?array $data = [];

    #[Locked]
    public ?ModelsProfile $record = null;

    public function mount(): void
    {
        $this->record = ModelsProfile::findOrNew(1);

        $this->fillForm();
    }

    public function fillForm(): void
    {
        $data = $this->record->attributesToArray();

        $this->form->fill($data);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                $this->getLogoForm(),
                $this->getIdentityFrom(),
            ])
            ->model($this->record)
            ->statePath('data')
            ->operation('edit');
    }

    public function save(): void
    {
        try {
            $data = $this->form->getState();

            $this->record->fill($data);

            $this->record->save();

        } catch (Halt $exception) {
            return;
        }

        $this->getSavedNotification()->send();
    }

    protected function getSavedNotification(): Notification
    {
        return Notification::make()
            ->success()
            ->title(__('filament-panels::resources/pages/edit-record.notifications.saved.title'));
    }

    public function getLogoForm(): Component
    {
        return Group::make()
            ->schema([
                Section::make('Logo')
                    ->schema([
                        CuratorPicker::make('media_id')
                            ->label('Choose Logo'),
                    ])
                    ->columnSpan(4),
                Section::make('Currency')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('currency_id')
                                    ->options(Currency::all()->pluck('abbr', 'id'))
                                    ->label('Currency')
                                    ->optionsLimit(40)
                                    ->searchable()
                                    ->createOptionForm(Currency::getForm())
                                    ->live()
                                    ->preload()
                                    ->getSearchResultsUsing(fn (string $search): array => Currency::whereAny([
                                        'name', 'abbr', 'symbol', 'code'], 'like', "%{$search}%")->limit(50)->pluck('abbr', 'id')->toArray())
                                    ->getOptionLabelUsing(fn ($value): ?string => Currency::find($value)?->abbr)
                                    ->loadingMessage('Loading currencies...')
                                    ->searchPrompt('Search currencies by their symbol, abbreviation or country')
                                    ->required(),
                                TextInput::make('exchange_rate_api')
                                    ->label('Exchange rate API Key')
                                    ->required(),
                            ]),
                    ])
                    ->columnSpan(8),
            ])
            ->columns(12);
    }

    public function getIdentityFrom(): Component
    {
        return Section::make('Identity')
            ->schema([
                TextInput::make('name'),
                TextInput::make('email')
                    ->email(),
                PhoneInput::make('phone')
                    ->defaultCountry('KE')
                    ->displayNumberFormat(PhoneInputNumberType::INTERNATIONAL)
                    ->focusNumberFormat(PhoneInputNumberType::INTERNATIONAL),
                TextInput::make('registration'),
                TextInput::make('kra_pin')
                    ->label('KRA Pin'),
                Select::make('entity')
                    ->enum(EntityType::class)
                    ->options(EntityType::class)
                    ->searchable()
                    ->preload()
                    ->default(EntityType::DEFAULT),
            ])
            ->columns(2);
    }

    /**
     * @return array<Action | ActionGroup>
     */
    public function getFormActions(): array
    {
        return [
            $this->getSaveFormAction(),
        ];
    }

    protected function getSaveFormAction(): Action
    {
        return Action::make('save')
            ->label(__('filament-panels::resources/pages/edit-record.form.actions.save.label'))
            ->submit('save');
    }
}
