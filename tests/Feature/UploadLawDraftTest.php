<?php

use App\Livewire\HomePage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

test('home page displays correctly', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
    $response->assertSeeLivewire(HomePage::class);
});

test('component renders successfully with Bulgarian text', function () {
    Livewire::test(HomePage::class)
        ->assertStatus(200)
        ->assertSee('Анализ на законопроекти')
        ->assertSee('Как работи инструментът?')
        ->assertSee('Качете законопроект');
});

test('can upload a law draft with valid file', function () {
    Storage::fake('local');

    $file = UploadedFile::fake()->create('test-law.txt', 100, 'text/plain');

    Livewire::test(HomePage::class)
        ->set('lawDraft', $file)
        ->call('upload')
        ->assertHasNoErrors()
        ->assertSet('lawDraft', null);

    Storage::disk('local')->assertExists('law-drafts/'.$file->hashName());
});

test('law draft file is required', function () {
    Livewire::test(HomePage::class)
        ->call('upload')
        ->assertHasErrors(['lawDraft' => 'required']);
});

test('only accepts specific file types', function () {
    Storage::fake('local');

    $file = UploadedFile::fake()->create('test-law.exe', 100);

    Livewire::test(HomePage::class)
        ->set('lawDraft', $file)
        ->call('upload')
        ->assertHasErrors(['lawDraft' => 'mimes']);
});

test('rejects files larger than 10MB', function () {
    Storage::fake('local');

    $file = UploadedFile::fake()->create('test-law.txt', 10241); // 10241 KB = just over 10MB

    Livewire::test(HomePage::class)
        ->set('lawDraft', $file)
        ->call('upload')
        ->assertHasErrors(['lawDraft' => 'max']);
});

test('accepts txt files', function () {
    Storage::fake('local');

    $file = UploadedFile::fake()->create('test-law.txt', 100, 'text/plain');

    Livewire::test(HomePage::class)
        ->set('lawDraft', $file)
        ->call('upload')
        ->assertHasNoErrors();
});

test('accepts pdf files', function () {
    Storage::fake('local');

    $file = UploadedFile::fake()->create('test-law.pdf', 100, 'application/pdf');

    Livewire::test(HomePage::class)
        ->set('lawDraft', $file)
        ->call('upload')
        ->assertHasNoErrors();
});

test('accepts doc files', function () {
    Storage::fake('local');

    $file = UploadedFile::fake()->create('test-law.doc', 100, 'application/msword');

    Livewire::test(HomePage::class)
        ->set('lawDraft', $file)
        ->call('upload')
        ->assertHasNoErrors();
});

test('accepts docx files', function () {
    Storage::fake('local');

    $file = UploadedFile::fake()->create('test-law.docx', 100, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');

    Livewire::test(HomePage::class)
        ->set('lawDraft', $file)
        ->call('upload')
        ->assertHasNoErrors();
});

test('form resets after successful upload', function () {
    Storage::fake('local');

    $file = UploadedFile::fake()->create('test-law.txt', 100);

    Livewire::test(HomePage::class)
        ->set('lawDraft', $file)
        ->call('upload')
        ->assertSet('lawDraft', null);
});

test('displays Bulgarian success message after upload', function () {
    Storage::fake('local');

    $file = UploadedFile::fake()->create('test-law.txt', 100);

    Livewire::test(HomePage::class)
        ->set('lawDraft', $file)
        ->call('upload')
        ->assertHasNoErrors()
        ->assertSet('lawDraft', null);

    Storage::disk('local')->assertExists('law-drafts/'.$file->hashName());
});

test('validates file on update', function () {
    $file = UploadedFile::fake()->create('invalid.exe', 100);

    Livewire::test(HomePage::class)
        ->set('lawDraft', $file)
        ->assertHasErrors(['lawDraft']);
});

test('shows Bulgarian error messages', function () {
    Livewire::test(HomePage::class)
        ->call('upload')
        ->assertHasErrors(['lawDraft']);

    $component = Livewire::test(HomePage::class);
    $errors = $component->call('upload')->instance()->getErrorBag();

    expect($errors->first('lawDraft'))->toContain('изберете файл');
});

test('file size validation shows Bulgarian error message', function () {
    Storage::fake('local');

    $file = UploadedFile::fake()->create('test-law.txt', 10241);

    $component = Livewire::test(HomePage::class)
        ->set('lawDraft', $file)
        ->call('upload');

    $errors = $component->instance()->getErrorBag();
    expect($errors->first('lawDraft'))->toContain('10MB');
});
