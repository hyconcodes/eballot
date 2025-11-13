<?php
use Livewire\Volt\Component;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

new class extends Component {
    public string $role_name = '';
    public string $permission_name = '';

    public function mount(): void
    {
        abort_unless(auth()->user()?->hasRole('superadmin'), 403);
    }

    public function createRole(): void
    {
        $this->validate([
            'role_name' => ['required', 'string', 'max:255', 'unique:roles,name'],
        ]);
        Role::create(['name' => $this->role_name, 'guard_name' => 'web']);
        session()->flash('status', 'Role created.');
        $this->reset('role_name');
    }

    public function createPermission(): void
    {
        $this->validate([
            'permission_name' => ['required', 'string', 'max:255', 'unique:permissions,name'],
        ]);
        Permission::create(['name' => $this->permission_name, 'guard_name' => 'web']);
        session()->flash('status', 'Permission created.');
        $this->reset('permission_name');
    }

    public function togglePermission(int $roleId, int $permissionId): void
    {
        $role = Role::findOrFail($roleId);
        $permission = Permission::findOrFail($permissionId);
        if ($role->hasPermissionTo($permission->name)) {
            $role->revokePermissionTo($permission);
        } else {
            $role->givePermissionTo($permission);
        }
    }

    public function deleteRole(int $roleId): void
    {
        $role = Role::findOrFail($roleId);
        if ($role->name === 'superadmin') {
            return;
        }
        $role->delete();
        session()->flash('status', 'Role deleted.');
    }

    public function deletePermission(int $permissionId): void
    {
        $permission = Permission::findOrFail($permissionId);
        $permission->delete();
        session()->flash('status', 'Permission deleted.');
    }

    public function revokePermission(int $roleId, int $permissionId): void
    {
        $role = Role::findOrFail($roleId);
        $permission = Permission::findOrFail($permissionId);
        $role->revokePermissionTo($permission);
    }
};
?>
<main>
    <section class="w-full">
        <flux:heading size="xl" level="1">{{ __('Roles & Permissions') }}</flux:heading>
        <flux:subheading size="lg" class="mt-1">{{ __('Create roles, permissions, and assign permissions to roles') }}
        </flux:subheading>

        <div class="mt-6 w-full">
            @if (session('status'))
                <flux:callout variant="success" icon="check-circle" heading="{{ session('status') }}" />
            @endif

            <div class="grid gap-6 md:grid-cols-2">
                <div class="rounded-xl border bg-white dark:bg-zinc-900 dark:border-zinc-800 shadow-xs">
                    <div class="px-6 py-6">
                        <flux:heading size="md">{{ __('Create Role') }}</flux:heading>
                        <form wire:submit="createRole" class="mt-4 flex flex-col gap-4">
                            <flux:input wire:model="role_name" :label="__('Role Name')" type="text"
                                placeholder="{{ __('e.g. supervisor') }}" />
                            @error('role_name')
                                <flux:text color="red" class="text-sm">{{ $message }}</flux:text>
                            @enderror
                            <flux:button type="submit" variant="primary" icon="plus">{{ __('Create Role') }}</flux:button>
                        </form>
                    </div>
                </div>

                <div class="rounded-xl border bg-white dark:bg-zinc-900 dark:border-zinc-800 shadow-xs">
                    <div class="px-6 py-6">
                        <flux:heading size="md">{{ __('Create Permission') }}</flux:heading>
                        <form wire:submit="createPermission" class="mt-4 flex flex-col gap-4">
                            <flux:input wire:model="permission_name" :label="__('Permission Name')" type="text"
                                placeholder="{{ __('e.g. manage-elections') }}" />
                            @error('permission_name')
                                <flux:text color="red" class="text-sm">{{ $message }}</flux:text>
                            @enderror
                            <flux:button type="submit" variant="primary" icon="plus">{{ __('Create Permission') }}</flux:button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-10 rounded-xl border bg-white dark:bg-zinc-900 dark:border-zinc-800 shadow-xs">
            <div class="px-6 py-6">
                <flux:heading size="md">{{ __('Assign Permissions to Roles') }}</flux:heading>
                @php($roles = Spatie\Permission\Models\Role::orderBy('name')->get())
                @php($permissions = Spatie\Permission\Models\Permission::orderBy('name')->get())
                <div class="mt-4 overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-zinc-500">
                                <th class="px-3 py-2 text-left">{{ __('Role') }}</th>
                                @foreach ($permissions as $permission)
                                    <th class="px-3 py-2 text-left whitespace-nowrap">{{ $permission->name }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($roles as $role)
                                <tr class="border-t">
                                    <td class="px-3 py-3 font-medium">{{ $role->name }}</td>
                                    @foreach ($permissions as $permission)
                                        <td class="px-3 py-3">
                                            <input type="checkbox"
                                                @checked($role->hasPermissionTo($permission->name))
                                                wire:click="togglePermission({{ $role->id }}, {{ $permission->id }})"
                                                class="h-4 w-4 rounded border-zinc-400 text-emerald-600 focus:ring-emerald-600">
                                        </td>
                                    @endforeach
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="999" class="px-3 py-6 text-center text-zinc-500">{{ __('No roles defined.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="mt-10 rounded-xl border bg-white dark:bg-zinc-900 dark:border-zinc-800 shadow-xs">
            <div class="px-6 py-6">
                <flux:heading size="md">{{ __('Roles') }}</flux:heading>
                @php($roles = Spatie\Permission\Models\Role::orderBy('name')->get())
                <div class="mt-4 flex flex-col gap-4">
                    @forelse ($roles as $role)
                        <div class="flex flex-col gap-2">
                            <div class="flex items-center gap-2">
                                <span class="font-medium">{{ $role->name }}</span>
                                @if ($role->name !== 'superadmin')
                                    <flux:button size="sm" variant="danger" icon="trash" wire:click="deleteRole({{ $role->id }})">{{ __('Delete') }}</flux:button>
                                @endif
                            </div>
                            <div class="flex flex-wrap gap-2">
                                @forelse ($role->permissions as $perm)
                                    <span class="inline-flex items-center gap-1 rounded-md border border-emerald-600/20 bg-emerald-600/10 px-2 py-1 text-xs text-emerald-700 dark:text-emerald-300">
                                        {{ $perm->name }}
                                        <button type="button" class="ms-1 inline-flex h-4 w-4 items-center justify-center rounded hover:bg-emerald-600/20" wire:click="revokePermission({{ $role->id }}, {{ $perm->id }})">×</button>
                                    </span>
                                @empty
                                    <span class="text-zinc-500 text-xs">{{ __('No permissions') }}</span>
                                @endforelse
                            </div>
                        </div>
                    @empty
                        <div class="text-zinc-500 text-sm">{{ __('No roles defined.') }}</div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="mt-6 rounded-xl border bg-white dark:bg-zinc-900 dark:border-zinc-800 shadow-xs">
            <div class="px-6 py-6">
                <flux:heading size="md">{{ __('All Permissions') }}</flux:heading>
                @php($permissions = Spatie\Permission\Models\Permission::orderBy('name')->get())
                <div class="mt-4 flex flex-wrap gap-2">
                    @forelse ($permissions as $perm)
                        <span class="inline-flex items-center gap-1 rounded-md border border-zinc-400/40 bg-zinc-100 px-2 py-1 text-xs text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">
                            {{ $perm->name }}
                            <flux:button size="xs" variant="danger" icon="trash" class="ms-1" wire:click="deletePermission({{ $perm->id }})">{{ __('Delete') }}</flux:button>
                        </span>
                    @empty
                        <span class="text-zinc-500 text-sm">{{ __('No permissions defined.') }}</span>
                    @endforelse
                </div>
            </div>
        </div>
    </section>
</main>