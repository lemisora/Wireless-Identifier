<?php

namespace App\Livewire;

use Livewire\Component;

class Counter extends Component
{
    public int $count = 0;

    public function increment()
    {
        $this->count++;
    }

    public function decrement()
    {
        $this->count--;
    }

    // Le decimos a este componente que use nuestro layout principal
    public function render()
    {
        return view('livewire.counter')
            ->layout('layouts.app');
    }
}