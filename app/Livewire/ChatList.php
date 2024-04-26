<?php

namespace App\Livewire;

use App\Models\Role;
use Livewire\Component;

class ChatList extends Component
{
    public function render()
    {
        $list = Role::find(Role::ADMIN)->users()->get();

        return view('livewire.chat-list', compact('list'));
    }
}
