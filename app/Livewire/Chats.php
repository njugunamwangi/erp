<?php

namespace App\Livewire;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

class Chats extends Component
{
    #[Layout('layouts.app')]
    #[Title('Chats')]
    public function render()
    {
        return view('livewire.chats');
    }
}
