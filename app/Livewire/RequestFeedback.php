<?php

namespace App\Livewire;

use App\Models\Feedback;
use App\Models\Lead;
use App\Models\Task;
use Filament\Actions\Action;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Concerns\InteractsWithFormActions;
use Filament\Pages\SimplePage;
use Filament\Support\Enums\MaxWidth;
use IbrahimBougaoua\FilamentRatingStar\Actions\RatingStar;
use Livewire\Component;

class RequestFeedback extends SimplePage
{
    use InteractsWithForms;
    use InteractsWithFormActions;

    protected static string $view = 'livewire.request-feedback';
    public int $task;
    private Task $taskModel;
    public ?array $data = [];

    public function mount() {
        $this->taskModel = Task::findOrFail($this->task);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Radio::make('lead_id')
                    ->label('How did you learn about our services?')
                    ->options(Lead::all()->pluck('lead', 'id'))
                    ->required()
                    ->columns(2),
                RatingStar::make('time')
                    ->required()
                    ->label('Time Keeping'),
                RatingStar::make('service')
                    ->required()
                    ->label('Service Quality'),
                RatingStar::make('safety')
                    ->required()
                    ->label('Safety Pre-Cations'),
                RatingStar::make('speed')
                    ->required()
                    ->label('Speed of the service'),
                RatingStar::make('overall')
                    ->required()
                    ->label('Overall Experience'),
                Textarea::make('comments')
                    ->label('Other comments')
                    ->rows(4)
            ])
            ->statePath('data');
    }

    public function create(): void
    {
        $this->taskModel = Task::find($this->task);

        Feedback::create([
            'task_id' => $this->task,
            'lead_id' => $this->form->getState()['lead_id'],
            'time' => $this->form->getState()['time'],
            'service' => $this->form->getState()['service'],
            'safety' => $this->form->getState()['safety'],
            'speed' => $this->form->getState()['speed'],
            'overall' => $this->form->getState()['overall'],
            'comments' => $this->form->getState()['comments'],
        ]);

        Notification::make()
            ->title('Feedback recorded')
            ->success()
            ->body('Thank you for taking your time to send feedback')
            ->send();

        redirect('success');
    }

    public function getRegisterFormAction(): Action
    {
        return Action::make('feedback')
            ->label('Send Feedback')
            ->submit('feedback');
    }

    /**
     * @return array<Action | ActionGroup>
     */
    public function getFormActions(): array
    {
        return [
            $this->getRegisterFormAction(),
        ];
    }

    public function getHeading(): string
    {
        return 'Feedback';
    }

    public function hasLogo(): bool
    {
        return false;
    }

    public function getSubHeading(): string
    {
        return 'Please fill in the form';
    }
}
