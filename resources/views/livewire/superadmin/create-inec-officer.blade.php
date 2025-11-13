<?php
use Livewire\Volt\Component;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Validation\Rule;
// use function Livewire\Volt\{title};

// title('INEC Administration');

new class extends Component {
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public ?int $editingId = null;
    public ?int $confirmDeleteId = null;
    public string $edit_name = '';
    public string $edit_email = '';
    public string $edit_password = '';
    public function mount(): void
    {
        abort_unless(auth()->user()?->hasRole('superadmin'), 403);
    }
    public function createOfficer(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $user = User::create([
            'name' => $this->name,
            'email' => $this->email,
            'password' => Hash::make($this->password),
        ]);

        $user->assignRole('inecofficer');

        session()->flash('status', 'INEC officer account created successfully.');
        $this->reset('name', 'email', 'password');
    }

    public function startEdit(int $id): void
    {
        $user = User::findOrFail($id);
        abort_unless($user->hasRole('inecofficer'), 403);
        $this->editingId = $id;
        $this->edit_name = $user->name;
        $this->edit_email = $user->email;
        $this->edit_password = '';
    }

    public function updateOfficer(): void
    {
        abort_unless($this->editingId !== null, 403);
        $this->validate([
            'edit_name' => ['required', 'string', 'max:255'],
            'edit_email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->editingId)],
            'edit_password' => ['nullable', 'string', 'min:8'],
        ]);

        $user = User::find($this->editingId);
        if (! $user || ! $user->hasRole('inecofficer')) {
            return;
        }
        $user->name = $this->edit_name;
        $user->email = $this->edit_email;
        if (filled($this->edit_password)) {
            $user->password = Hash::make($this->edit_password);
        }
        $user->save();

        session()->flash('status', 'INEC officer account updated.');
        $this->editingId = null;
        $this->reset('edit_name', 'edit_email', 'edit_password');
    }

    public function confirmDelete(int $id): void
    {
        $this->confirmDeleteId = $id;
    }

    public function cancelDelete(): void
    {
        $this->confirmDeleteId = null;
    }

    public function deleteOfficer(): void
    {
        if ($this->confirmDeleteId === null) {
            return;
        }
        $user = User::find($this->confirmDeleteId);
        if ($user && $user->hasRole('inecofficer')) {
            $user->delete();
            session()->flash('status', 'INEC officer account deleted.');
        }
        $this->confirmDeleteId = null;
    }
};
?>
<main>
    <section class="w-full">
        <flux:heading size="xl" level="1">{{ __('Create INEC Officer') }}</flux:heading>
        <flux:subheading size="lg" class="mt-1">{{ __('Provision accounts for INEC officers') }}
        </flux:subheading>

        <div class="mt-6 w-full max-w-xl">
            @if (session('status'))
                <flux:callout variant="success" icon="check-circle" heading="{{ session('status') }}" />
            @endif

            <div class="rounded-xl border bg-white dark:bg-zinc-900 dark:border-zinc-800 shadow-xs">
                <div class="px-6 py-6">
                    <form wire:submit="createOfficer" class="flex flex-col gap-5">
                        <flux:input wire:model="name" :label="__('Full Name')" type="text" autocomplete="name"
                            placeholder="Full Name" />
                        @error('name')
                            <flux:text color="red" class="text-sm">{{ $message }}</flux:text>
                        @enderror

                        <flux:input wire:model="email" :label="__('Email')" type="email" autocomplete="email"
                            placeholder="email@example.com" />
                        @error('email')
                            <flux:text color="red" class="text-sm">{{ $message }}</flux:text>
                        @enderror

                        <flux:input wire:model="password" :label="__('Password')" type="password"
                            autocomplete="new-password" placeholder="Password" viewable />
                        @error('password')
                            <flux:text color="red" class="text-sm">{{ $message }}</flux:text>
                        @enderror

                        <div class="flex items-center gap-3 pt-2">
                            <flux:button type="submit" variant="primary" icon="user-plus"
                                class="min-w-[160px] hover:scale-105 transition">
                                {{ __('Create Officer') }}
                            </flux:button>
                            <flux:link :href="route('dashboard')" wire:navigate>{{ __('Cancel') }}</flux:link>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="mt-10 rounded-xl border bg-white dark:bg-zinc-900 dark:border-zinc-800 shadow-xs">
            <div class="px-6 py-6">
                <flux:heading size="md">{{ __('Existing Officers') }}</flux:heading>
                @php($officers = App\Models\User::role('inecofficer')->orderByDesc('created_at')->get())
                <div class="mt-4 overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-zinc-500">
                                <th class="px-3 py-2 text-left">{{ __('Name') }}</th>
                                <th class="px-3 py-2 text-left">{{ __('Email') }}</th>
                                <th class="px-3 py-2 text-left">{{ __('Created') }}</th>
                                <th class="px-3 py-2 text-left">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($officers as $officer)
                                <tr class="border-t">
                                    <td class="px-3 py-3">
                                        @if ($editingId === $officer->id)
                                            <flux:input wire:model="edit_name" type="text" />
                                        @else
                                            {{ $officer->name }}
                                        @endif
                                    </td>
                                    <td class="px-3 py-3">
                                        @if ($editingId === $officer->id)
                                            <flux:input wire:model="edit_email" type="email" />
                                        @else
                                            {{ $officer->email }}
                                        @endif
                                    </td>
                                    <td class="px-3 py-3">{{ $officer->created_at->format('Y-m-d') }}</td>
                                    <td class="px-3 py-3">
                                        @if ($editingId === $officer->id)
                                            <div class="flex items-center gap-2">
                                                <flux:button variant="primary" wire:click="updateOfficer">{{ __('Save') }}</flux:button>
                                                <flux:button variant="outline" wire:click="$set('editingId', null)">{{ __('Cancel') }}</flux:button>
                                            </div>
                                            <div class="mt-2">
                                                <flux:input wire:model="edit_password" type="password" :label="__('New Password')" placeholder="{{ __('Optional') }}" viewable />
                                            </div>
                                        @else
                                            <div class="flex items-center gap-2">
                                                <flux:button variant="outline" icon="pencil-square" wire:click="startEdit({{ $officer->id }})">{{ __('Edit') }}</flux:button>
                                                @if ($confirmDeleteId === $officer->id)
                                                    <div class="flex items-center gap-2">
                                                        <flux:button variant="danger" wire:click="deleteOfficer">{{ __('Confirm') }}</flux:button>
                                                        <flux:button variant="filled" wire:click="cancelDelete">{{ __('Cancel') }}</flux:button>
                                                    </div>
                                                @else
                                                    <flux:button variant="danger" icon="trash" wire:click="confirmDelete({{ $officer->id }})">{{ __('Delete') }}</flux:button>
                                                @endif
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-3 py-6 text-center text-zinc-500">{{ __('No officers yet.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
</main>
