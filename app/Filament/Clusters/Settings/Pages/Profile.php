<?php

namespace App\Filament\Clusters\Settings\Pages;

use App\Enums\EntityType;
use App\Filament\Clusters\Settings;
use App\Models\Profile as ModelsProfile;
use Awcodes\Curator\Components\Forms\CuratorPicker;
use Filament\Actions\Action;
use Filament\Forms\Components\Component;
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
        return Section::make('Logo')
            ->schema([
                CuratorPicker::make('media_id')
                    ->label('Choose Logo'),
            ])
            ->columns(3);
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
