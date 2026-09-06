<?php

namespace App\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class StudentLayout extends Component
{
    public function __construct(
        public ?string $title = null,
        public ?string $heading = null
    ) {
        $this->heading = $heading ?? $title ?? 'Student Portal';
    }

    public function render(): View
    {
        return view('student.layout', [
            'title'   => $this->title,
            'heading' => $this->heading,
        ]);
    }
}
