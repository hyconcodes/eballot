<?php

use Livewire\Volt\Component;
use App\Models\VoterVerification;
use Illuminate\Support\Facades\Auth;

new class extends Component {
    public ?int $reviewId = null;
    public string $notes = '';
    public string $state = '';

    public function mount(): void
    {
        abort_unless(auth()->user()?->hasAnyRole('inecofficer|superadmin'), 403);
    }

    public function with(): array
    {
        return [
            'pending' => VoterVerification::where('status', 'pending')->orderBy('created_at')->get(),
            'recent' => VoterVerification::whereIn('status', ['approved','rejected'])->orderByDesc('updated_at')->limit(10)->get(),
            'states' => [
                'Abia','Adamawa','Akwa Ibom','Anambra','Bauchi','Bayelsa','Benue','Borno','Cross River','Delta','Ebonyi','Edo','Ekiti','Enugu','Gombe','Imo','Jigawa','Kaduna','Kano','Katsina','Kebbi','Kogi','Kwara','Lagos','Nasarawa','Niger','Ogun','Ondo','Osun','Oyo','Plateau','Rivers','Sokoto','Taraba','Yobe','Zamfara','FCT',
            ],
        ];
    }

    public function setReview(int $id): void
    {
        $this->reviewId = $id;
        $this->notes = '';
        $v = VoterVerification::find($id);
        $this->state = $v?->state ?? '';
    }

    public function approve(): void
    {
        if (! $this->reviewId) return;
        if (! $this->state) {
            session()->flash('warning', 'Please select the voter\'s state before approving.');
            return;
        }
        $v = VoterVerification::findOrFail($this->reviewId);
        $v->update([
            'status' => 'approved',
            'verified_by' => Auth::id(),
            'verified_at' => now(),
            'notes' => $this->notes ?: null,
            'state' => $this->state,
        ]);
        $this->reviewId = null;
        $this->notes = '';
        $this->state = '';
        session()->flash('status', 'Voter approved.');
    }

    public function reject(): void
    {
        if (! $this->reviewId) return;
        $v = VoterVerification::findOrFail($this->reviewId);
        $v->update([
            'status' => 'rejected',
            'verified_by' => Auth::id(),
            'verified_at' => now(),
            'notes' => $this->notes ?: null,
            'state' => $this->state ?: $v->state,
        ]);
        $this->reviewId = null;
        $this->notes = '';
        $this->state = '';
        session()->flash('status', 'Voter rejected.');
    }
};
?>

<main>
    <section class="w-full">
        <flux:heading size="xl" level="1">{{ __('Verify Voters') }}</flux:heading>
        <flux:subheading size="lg" class="mt-1">{{ __('Review and approve/reject voter submissions') }}</flux:subheading>

        <div class="mt-6 w-full">
            @if (session('status'))
                <flux:callout variant="success" icon="check-circle" heading="{{ session('status') }}" />
            @endif
            @if (session('warning'))
                <flux:callout variant="warning" icon="exclamation-triangle" heading="{{ session('warning') }}" />
            @endif

            <div class="rounded-xl border bg-white dark:bg-zinc-900 dark:border-zinc-800 shadow-xs">
                <div class="px-6 py-6">
                    <flux:heading size="lg">{{ __('Pending Reviews') }}</flux:heading>
                    <div class="mt-4 overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="text-left">
                                    <th class="py-2 pe-4">{{ __('Voter') }}</th>
                                    <th class="py-2 pe-4">{{ __('Election') }}</th>
                                    <th class="py-2 pe-4">{{ __('NIN') }}</th>
                                    <th class="py-2 pe-4">{{ __('NIN Images') }}</th>
                                    <th class="py-2 pe-4">{{ __('Voters Card') }}</th>
                                    <th class="py-2">{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($pending as $v)
                                    <tr class="border-t dark:border-zinc-800">
                                        <td class="py-2 pe-4">{{ $v->user->name }}</td>
                                        <td class="py-2 pe-4">{{ $v->election->title }}</td>
                                        <td class="py-2 pe-4">{{ $v->nin_number }}</td>
                                        <td class="py-2 pe-4">
                                            <div class="flex items-center gap-2">
                                                <img src="{{ asset('storage/'.$v->nin_front_path) }}" alt="NIN Front" class="h-12 w-20 rounded object-cover" />
                                                <img src="{{ asset('storage/'.$v->nin_back_path) }}" alt="NIN Back" class="h-12 w-20 rounded object-cover" />
                                            </div>
                                        </td>
                                        <td class="py-2 pe-4">
                                            <div class="flex items-center gap-2">
                                                <img src="{{ asset('storage/'.$v->voters_card_front_path) }}" alt="Card Front" class="h-12 w-20 rounded object-cover" />
                                                <img src="{{ asset('storage/'.$v->voters_card_back_path) }}" alt="Card Back" class="h-12 w-20 rounded object-cover" />
                                            </div>
                                        </td>
                                        <td class="py-2">
                                            <div class="flex items-center gap-2">
                                                <flux:button size="sm" variant="ghost" wire:click="setReview({{ $v->id }})">{{ __('Review') }}</flux:button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td class="py-4" colspan="5">{{ __('No pending verifications.') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="mt-8 rounded-xl border bg-white dark:bg-zinc-900 dark:border-zinc-800 shadow-xs">
                <div class="px-6 py-6">
                    <flux:heading size="lg">{{ __('Recent Decisions') }}</flux:heading>
                    <div class="mt-4 overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="text-left">
                                    <th class="py-2 pe-4">{{ __('Voter') }}</th>
                                    <th class="py-2 pe-4">{{ __('Election') }}</th>
                                    <th class="py-2 pe-4">{{ __('Status') }}</th>
                                    <th class="py-2 pe-4">{{ __('Notes') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($recent as $v)
                                    <tr class="border-t dark:border-zinc-800">
                                        <td class="py-2 pe-4">{{ $v->user->name }}</td>
                                        <td class="py-2 pe-4">{{ $v->election->title }}</td>
                                        <td class="py-2 pe-4">{{ ucfirst($v->status) }}</td>
                                        <td class="py-2 pe-4">{{ $v->notes }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td class="py-4" colspan="3">{{ __('No recent decisions.') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <flux:modal name="review-verification" class="max-w-2xl" wire:model="reviewId">
        @php($v = $reviewId ? App\Models\VoterVerification::find($reviewId) : null)
        @if ($v)
            <div class="space-y-6">
                <flux:heading size="lg">{{ $v->user->name }}</flux:heading>
                <flux:text>{{ $v->election->title }} • {{ $v->election->start_at->format('M j, Y g:i A') }} — {{ $v->election->end_at->format('M j, Y g:i A') }}</flux:text>
                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <flux:heading size="sm">{{ __('NIN') }}</flux:heading>
                        <flux:text>{{ $v->nin_number }}</flux:text>
                        <div class="mt-2 space-y-3">
                            <img src="{{ asset('storage/'.$v->nin_front_path) }}" alt="NIN Front" class="h-24 w-full rounded object-cover" />
                            <img src="{{ asset('storage/'.$v->nin_back_path) }}" alt="NIN Back" class="h-24 w-full rounded object-cover" />
                        </div>
                    </div>
                    <div>
                        <flux:heading size="sm">{{ __('Voters Card') }}</flux:heading>
                        <div class="mt-2 space-y-3">
                            <img src="{{ asset('storage/'.$v->voters_card_front_path) }}" alt="Card Front" class="h-24 w-full rounded object-cover" />
                            <img src="{{ asset('storage/'.$v->voters_card_back_path) }}" alt="Card Back" class="h-24 w-full rounded object-cover" />
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium">{{ __('Select State') }}</label>
                        <select wire:model="state" class="mt-2 block w-full rounded border dark:border-zinc-700 bg-white dark:bg-zinc-900 p-2 text-sm">
                            <option value="">{{ __('Choose...') }}</option>
                            @foreach ($states as $s)
                                <option value="{{ $s }}">{{ $s }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <flux:textarea :label="__('Notes')" wire:model="notes" rows="3" />
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <flux:button variant="danger" wire:click="reject">{{ __('Reject') }}</flux:button>
                    <flux:spacer />
                    <flux:button variant="primary" wire:click="approve">{{ __('Approve') }}</flux:button>
                </div>
            </div>
        @endif
    </flux:modal>
</main>