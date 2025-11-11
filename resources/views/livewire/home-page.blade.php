<div class="w-full max-w-5xl">
    <!-- Hero Section -->
    <div class="mb-12 text-center">
        <flux:heading size="2xl" class="mb-4 text-zinc-900 dark:text-white">
            Анализ на законопроекти
        </flux:heading>
        <flux:text class="text-lg text-zinc-600 dark:text-zinc-400">
            Качете законопроект и получете детайлен анализ на структурата му
        </flux:text>
    </div>

    <!-- How It Works Section -->
    <div class="mb-12 rounded-xl border border-neutral-200 bg-white p-8 dark:border-neutral-700 dark:bg-zinc-800/50">
        <flux:heading size="lg" class="mb-6 text-zinc-900 dark:text-white">
            Как работи инструментът?
        </flux:heading>

        <div class="grid gap-6 md:grid-cols-3">
            <!-- Step 1 -->
            <div class="flex flex-col items-start">
                <div class="mb-4 flex size-12 items-center justify-center rounded-lg bg-green-100 dark:bg-green-900/20">
                    <flux:icon.arrow-up-tray class="size-6 text-green-600 dark:text-green-500" />
                </div>
                <flux:heading size="sm" class="mb-2 text-zinc-900 dark:text-white">
                    1. Качване на документ
                </flux:heading>
                <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                    Качете вашия законопроект в TXT, DOC, DOCX или PDF формат. Максималният размер е 10MB.
                </flux:text>
            </div>

            <!-- Step 2 -->
            <div class="flex flex-col items-start">
                <div class="mb-4 flex size-12 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900/20">
                    <flux:icon.cpu-chip class="size-6 text-blue-600 dark:text-blue-500" />
                </div>
                <flux:heading size="sm" class="mb-2 text-zinc-900 dark:text-white">
                    2. Автоматичен анализ
                </flux:heading>
                <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                    Системата автоматично разпознава структурата - раздели, глави, членове, алинеи, точки и букви.
                </flux:text>
            </div>

            <!-- Step 3 -->
            <div class="flex flex-col items-start">
                <div class="mb-4 flex size-12 items-center justify-center rounded-lg bg-purple-100 dark:bg-purple-900/20">
                    <flux:icon.document-text class="size-6 text-purple-600 dark:text-purple-500" />
                </div>
                <flux:heading size="sm" class="mb-2 text-zinc-900 dark:text-white">
                    3. Резултати
                </flux:heading>
                <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                    Получавате йерархично структуриран документ, готов за анализ и сравнение с други законопроекти.
                </flux:text>
            </div>
        </div>
    </div>

    <!-- Upload Section -->
    <div class="rounded-xl border border-neutral-200 bg-white p-8 shadow-xl dark:border-neutral-700 dark:bg-zinc-900">
        <flux:heading size="lg" class="mb-6 text-center text-zinc-900 dark:text-white">
            Качете законопроект
        </flux:heading>

        <!-- Success Message -->
        @if (session()->has('message'))
            <flux:callout variant="success" class="mb-6">
                <flux:text>
                    {{ session('message') }}
                    @if (session()->has('uploadedFile'))
                        <br>
                        <strong>Файл:</strong> {{ session('uploadedFile') }}
                    @endif
                </flux:text>
            </flux:callout>
        @endif

        <!-- Error Message -->
        @if (session()->has('error'))
            <flux:callout variant="danger" class="mb-6">
                <flux:text>{{ session('error') }}</flux:text>
            </flux:callout>
        @endif

        <!-- Upload Form -->
        <form wire:submit="upload" class="space-y-6">
            <!-- File Upload using FluxUI Component -->
            <flux:file-upload wire:model="lawDraft" accept=".txt,.doc,.docx,.pdf">
                <flux:file-upload.dropzone
                    heading="Пуснете файла тук или кликнете за избор"
                    text="Поддържани формати: TXT, DOC, DOCX, PDF • Максимален размер: 10MB"
                    with-progress
                />
            </flux:file-upload>

            <!-- Validation Error -->
            @error('lawDraft')
                <flux:text class="text-sm text-red-600 dark:text-red-400">
                    {{ $message }}
                </flux:text>
            @enderror

            <!-- File Preview -->
            @if ($lawDraft)
                <div class="rounded-lg border border-neutral-200 bg-zinc-50 p-4 dark:border-neutral-700 dark:bg-zinc-800">
                    <flux:file-item
                        :heading="$lawDraft->getClientOriginalName()"
                        :size="$lawDraft->getSize()"
                    >
                        <x-slot name="actions">
                            <flux:file-item.remove wire:click="$set('lawDraft', null)" />
                        </x-slot>
                    </flux:file-item>
                </div>
            @endif

            <!-- Submit Button -->
            <div class="flex justify-center">
                <flux:button
                    type="submit"
                    variant="primary"
                    :disabled="!$lawDraft"
                    wire:loading.attr="disabled"
                    wire:target="upload"
                    class="min-w-[200px]"
                >
                    <span wire:loading.remove wire:target="upload">Анализирай законопроект</span>
                    <span wire:loading wire:target="upload">Обработка...</span>
                </flux:button>
            </div>
        </form>
    </div>

    <!-- Features Section -->
    <div class="mt-12 grid gap-6 md:grid-cols-3">
        <div class="rounded-lg border border-neutral-200 bg-white p-6 dark:border-neutral-700 dark:bg-zinc-800/50">
            <div class="mb-3 flex items-center gap-3">
                <flux:icon.shield-check class="size-6 text-green-600 dark:text-green-500" />
                <flux:heading size="sm" class="text-zinc-900 dark:text-white">Сигурност</flux:heading>
            </div>
            <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                Вашите документи се обработват сигурно и се съхраняват криптирано на нашите сървъри.
            </flux:text>
        </div>

        <div class="rounded-lg border border-neutral-200 bg-white p-6 dark:border-neutral-700 dark:bg-zinc-800/50">
            <div class="mb-3 flex items-center gap-3">
                <flux:icon.bolt class="size-6 text-yellow-600 dark:text-yellow-500" />
                <flux:heading size="sm" class="text-zinc-900 dark:text-white">Бързина</flux:heading>
            </div>
            <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                Автоматичният анализ се извършва за секунди, спестявайки ви часове ръчна работа.
            </flux:text>
        </div>

        <div class="rounded-lg border border-neutral-200 bg-white p-6 dark:border-neutral-700 dark:bg-zinc-800/50">
            <div class="mb-3 flex items-center gap-3">
                <flux:icon.chart-bar class="size-6 text-blue-600 dark:text-blue-500" />
                <flux:heading size="sm" class="text-zinc-900 dark:text-white">Точност</flux:heading>
            </div>
            <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                Прецизно разпознаване на структурата съгласно българските законодателни стандарти.
            </flux:text>
        </div>
    </div>
</div>
