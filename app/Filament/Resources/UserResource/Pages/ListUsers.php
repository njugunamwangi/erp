<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Mail\TeamInvitationMail;
use App\Models\Invitation;
use App\Models\Stage;
use App\Models\User;
use Filament\Actions;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Mail;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->icon('heroicon-o-user-plus'),
            Actions\Action::make('inviteUser')
                ->icon('heroicon-o-envelope')
                ->modalIcon('heroicon-o-envelope')
                ->form([
                    TextInput::make('email')
                        ->email()
                        ->required()
                ])
                ->action(function ($data) {
                    $invitation = Invitation::create(['email' => $data['email']]);

                    Mail::to($invitation->email)->send(new TeamInvitationMail($invitation));

                    Notification::make('invitedSuccess')
                        ->body('User invited successfully!')
                        ->success()->send();
                }),
        ];
    }

    public function getTabs(): array
    {
        $tabs = [];

        $tabs['all'] = Tab::make('All Users')
            ->badge(User::count());

        $stages = Stage::orderBy('position')->withCount('users')->get();

        foreach ($stages as $stage) {
            $tabs[str($stage->stage)->slug()->toString()] = Tab::make($stage->stage)
                ->badge($stage->users_count)
                ->modifyQueryUsing(function ($query) use ($stage) {
                    return $query->where('stage_id', $stage->id);
                });
        }

        $tabs['archived'] = Tab::make('Archived')
            ->badge(User::onlyTrashed()->count())
            ->modifyQueryUsing(function ($query) {
                return $query->onlyTrashed();
            });

        return $tabs;
    }
}
