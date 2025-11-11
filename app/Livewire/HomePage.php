<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;

class HomePage extends Component
{
    use WithFileUploads;

    public $lawDraft;

    public function rules(): array
    {
        return [
            'lawDraft' => ['required', 'file', 'mimes:txt,doc,docx,pdf', 'max:10240'], // Max 10MB
        ];
    }

    public function messages(): array
    {
        return [
            'lawDraft.required' => 'Моля, изберете файл със законопроект.',
            'lawDraft.mimes' => 'Файлът трябва да бъде текстов документ, Word документ или PDF.',
            'lawDraft.max' => 'Размерът на файла не трябва да надвишава 10MB.',
        ];
    }

    public function updatedLawDraft(): void
    {
        $this->validateOnly('lawDraft');
    }

    public function upload(): void
    {
        $this->validate();

        try {
            // Store the file
            $path = $this->lawDraft->store('law-drafts', 'local');

            // Here you would typically:
            // 1. Save the file information to the database
            // 2. Process the file content
            // 3. Create a new Law record

            session()->flash('message', 'Законопроектът е качен успешно!');
            session()->flash('uploadedFile', $this->lawDraft->getClientOriginalName());

            // Reset form
            $this->reset(['lawDraft']);
        } catch (\Exception $e) {
            session()->flash('error', 'Грешка при качването на файла. Моля, опитайте отново.');
        }
    }

    public function render()
    {
        return view('livewire.home-page')
            ->layout('components.layouts.guest');
    }
}
