<?php

use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use App\Models\Election;

new class extends Component {
    use WithFileUploads;

    public string $title = '';
    public string $description = '';
    public string $start_at = '';
    public string $end_at = '';
    public $banner = null;
    public bool $hasOngoing = false;
    public ?int $editingId = null;
    public ?int $confirmDeleteId = null;
    public string $edit_title = '';
    public string $edit_description = '';
    public string $edit_start_at = '';
    public string $edit_end_at = '';

    public function mount(): void
    {
        abort_unless(auth()->user()?->hasRole('superadmin'), 403);
        $this->hasOngoing = Election::where('type', 'presidential')
            ->where('start_at', '<=', now())
            ->where('end_at', '>=', now())
            ->where('is_paused', false)
            ->exists();
    }

    public function save(): void
    {
        if ($this->hasOngoing) {
            session()->flash('warning', 'An election is currently ongoing. Finish it before creating a new one.');
            return;
        }

        $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'start_at' => ['required', 'date'],
            'end_at' => ['required', 'date'],
            'banner' => ['nullable', 'image', 'max:2048'],
        ]);

        $path = null;
        if (! empty($this->banner)) {
            $path = $this->banner->store('elections', 'public');
        }

        Election::create([
            'title' => $this->title,
            'description' => $this->description ?: null,
            'type' => 'presidential',
            'start_at' => $this->start_at,
            'end_at' => $this->end_at,
            'banner_path' => $path,
            'is_paused' => false,
        ]);

        session()->flash('status', 'Presidential election saved successfully.');
        $this->reset('title', 'description', 'start_at', 'end_at', 'banner');
        $this->hasOngoing = Election::where('type', 'presidential')
            ->where('start_at', '<=', now())
            ->where('end_at', '>=', now())
            ->where('is_paused', false)
            ->exists();
    }

    public function with(): array
    {
        return [
            'elections' => Election::where('type', 'presidential')->orderByDesc('start_at')->get(),
        ];
    }

    public function statusLabel(Election $election): string
    {
        if ($election->is_paused) {
            return 'Paused';
        }
        if (now()->lt($election->start_at)) {
            return 'Scheduled';
        }
        if (now()->gt($election->end_at)) {
            return 'Ended';
        }
        return 'Ongoing';
    }

    public function startEdit(int $id): void
    {
        $election = Election::findOrFail($id);
        $this->editingId = $id;
        $this->edit_title = $election->title;
        $this->edit_description = $election->description ?? '';
        $this->edit_start_at = $election->start_at?->format('Y-m-d\\TH:i') ?? '';
        $this->edit_end_at = $election->end_at?->format('Y-m-d\\TH:i') ?? '';
    }

    public function cancelEdit(): void
    {
        $this->editingId = null;
        $this->reset('edit_title', 'edit_description', 'edit_start_at', 'edit_end_at');
    }

    public function updateElection(): void
    {
        if (! $this->editingId) {
            return;
        }

        $this->validate([
            'edit_title' => ['required', 'string', 'max:255'],
            'edit_description' => ['nullable', 'string'],
            'edit_start_at' => ['required', 'date'],
            'edit_end_at' => ['required', 'date'],
        ]);

        $election = Election::findOrFail($this->editingId);
        $election->update([
            'title' => $this->edit_title,
            'description' => $this->edit_description ?: null,
            'start_at' => $this->edit_start_at,
            'end_at' => $this->edit_end_at,
        ]);

        $this->cancelEdit();
        session()->flash('status', 'Election updated successfully.');
        $this->hasOngoing = Election::where('type', 'presidential')
            ->where('start_at', '<=', now())
            ->where('end_at', '>=', now())
            ->where('is_paused', false)
            ->exists();
    }

    public function togglePause(int $id): void
    {
        $election = Election::findOrFail($id);
        $election->is_paused = ! $election->is_paused;
        $election->save();
        session()->flash('status', $election->is_paused ? 'Election paused.' : 'Election resumed.');
        $this->hasOngoing = Election::where('type', 'presidential')
            ->where('start_at', '<=', now())
            ->where('end_at', '>=', now())
            ->where('is_paused', false)
            ->exists();
    }

    public function confirmDelete(int $id): void
    {
        $this->confirmDeleteId = $id;
    }

    public function cancelDelete(): void
    {
        $this->confirmDeleteId = null;
    }

    public function deleteElection(): void
    {
        if (! $this->confirmDeleteId) {
            return;
        }
        $election = Election::findOrFail($this->confirmDeleteId);
        $election->delete();
        $this->confirmDeleteId = null;
        session()->flash('status', 'Election deleted.');
        $this->hasOngoing = Election::where('type', 'presidential')
            ->where('start_at', '<=', now())
            ->where('end_at', '>=', now())
            ->where('is_paused', false)
            ->exists();
    }
};
?>

<main>
    <section class="w-full">
        <flux:heading size="xl" level="1">{{ __('Presidential Elections') }}</flux:heading>
        <flux:subheading size="lg" class="mt-1">{{ __('Create and upload election details') }}</flux:subheading>

        <div class="mt-6 w-full max-w-2xl">
            @if (session('status'))
                <flux:callout variant="success" icon="check-circle" heading="{{ session('status') }}" />
            @endif
            @if ($hasOngoing)
                <flux:callout variant="primary" color="yellow" icon="exclamation-triangle" heading="{{ __('An election is currently ongoing') }}">
                    <flux:text>{{ __('Finish the ongoing election before creating a new one.') }}</flux:text>
                </flux:callout>
            @endif

            <div class="rounded-xl border bg-white dark:bg-zinc-900 dark:border-zinc-800 shadow-xs">
                <div class="px-6 py-6">
                    <form wire:submit="save" class="flex flex-col gap-6">
                        <div class="grid gap-4 md:grid-cols-2">
                            <flux:input wire:model="title" :label="__('Title')" type="text" placeholder="e.g. 2027 Presidential Election" />
                            <flux:input wire:model="description" :label="__('Description')" type="text" placeholder="Short description" />
                            <flux:input wire:model="start_at" :label="__('Start')" type="datetime-local" />
                            <flux:input wire:model="end_at" :label="__('End')" type="datetime-local" />
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium">{{ __('Banner Image') }}</label>
                                <input type="file" wire:model="banner" class="mt-2 block w-full text-sm" />
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <flux:spacer />
                            <flux:button type="submit" variant="primary" icon="check" :disabled="$hasOngoing">{{ __('Save') }}</flux:button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
    <section class="w-full mt-10">
        <flux:heading size="lg" level="2">{{ __('Existing Elections') }}</flux:heading>
        <div class="mt-4 w-full">
            <div class="rounded-xl border bg-white dark:bg-zinc-900 dark:border-zinc-800 shadow-xs">
                <div class="px-6 py-4">
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="text-left">
                                    <th class="py-2 pe-4">{{ __('Title') }}</th>
                                    <th class="py-2 pe-4">{{ __('Window') }}</th>
                                    <th class="py-2 pe-4">{{ __('Status') }}</th>
                                    <th class="py-2">{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($elections as $election)
                                    <tr class="border-t dark:border-zinc-800">
                                        <td class="py-2 pe-4">
                                            @if ($editingId === $election->id)
                                                <div class="flex flex-col gap-2">
                                                    <flux:input wire:model="edit_title" type="text" />
                                                    <flux:input wire:model="edit_description" type="text" placeholder="Description" />
                                                </div>
                                            @else
                                                <a href="{{ route('superadmin.elections.show', $election) }}" wire:navigate class="text-primary-600 hover:underline">{{ $election->title }}</a>
                                            @endif
                                        </td>
                                        <td class="py-2 pe-4">
                                            @if ($editingId === $election->id)
                                                <div class="grid gap-2 md:grid-cols-2">
                                                    <flux:input wire:model="edit_start_at" type="datetime-local" />
                                                    <flux:input wire:model="edit_end_at" type="datetime-local" />
                                                </div>
                                            @else
                                                {{ $election->start_at->format('M j, Y g:i A') }} — {{ $election->end_at->format('M j, Y g:i A') }}
                                            @endif
                                        </td>
                                        <td class="py-2 pe-4">
                                            {{ $this->statusLabel($election) }}
                                        </td>
                                        <td class="py-2">
                                            @if ($editingId === $election->id)
                                                <div class="flex items-center gap-2">
                                                    <flux:button size="sm" variant="primary" wire:click="updateElection">{{ __('Save') }}</flux:button>
                                                    <flux:button size="sm" variant="ghost" wire:click="cancelEdit">{{ __('Cancel') }}</flux:button>
                                                </div>
                                            @else
                                                <div class="flex items-center gap-2">
                                                    <flux:button size="sm" variant="ghost" wire:click="startEdit({{ $election->id }})">{{ __('Edit') }}</flux:button>
                                                    @if ($election->is_paused)
                                                        <flux:button size="sm" variant="primary" color="yellow" wire:click="togglePause({{ $election->id }})">{{ __('Resume') }}</flux:button>
                                                    @else
                                                        <flux:button size="sm" variant="primary" color="yellow" wire:click="togglePause({{ $election->id }})">{{ __('Pause') }}</flux:button>
                                                    @endif
                                                    <flux:button size="sm" variant="danger" wire:click="confirmDelete({{ $election->id }})">{{ __('Delete') }}</flux:button>
                                                </div>
                                            @endif

                                            @if ($confirmDeleteId === $election->id)
                                                <div class="mt-2 flex items-center gap-2">
                                                    <flux:text>{{ __('Confirm delete?') }}</flux:text>
                                                    <flux:button size="sm" variant="danger" wire:click="deleteElection">{{ __('Confirm') }}</flux:button>
                                                    <flux:button size="sm" variant="ghost" wire:click="cancelDelete">{{ __('Cancel') }}</flux:button>
                                                </div>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td class="py-4" colspan="4">{{ __('No elections found.') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>