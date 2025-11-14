<?php

use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use App\Models\Election;
use App\Models\Candidate;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

new class extends Component {
    use WithFileUploads;

    public Election $election;

    public array $newCandidates = [];
    public bool $showEditCandidateModal = false;
    public ?int $editingCandidateId = null;
    public string $edit_candidate_name = '';
    public string $edit_candidate_party = '';
    public string $edit_candidate_bio = '';
    public $edit_candidate_photo = null;

    public function mount(Election $election): void
    {
        abort_unless(auth()->user()?->hasRole('superadmin'), 403);
        $this->election = $election;
        $this->ensureTwoRows();
    }

    public function with(): array
    {
        return [
            'candidates' => $this->election->candidates()->orderBy('name')->get(),
            'voteCounts' => DB::table('votes')
                ->select('candidate_id', DB::raw('count(*) as count'))
                ->where('election_id', $this->election->id)
                ->groupBy('candidate_id')
                ->pluck('count', 'candidate_id')
                ->toArray(),
            'states' => [
                'Abia','Adamawa','Akwa Ibom','Anambra','Bauchi','Bayelsa','Benue','Borno','Cross River','Delta','Ebonyi','Edo','Ekiti','Enugu','Gombe','Imo','Jigawa','Kaduna','Kano','Katsina','Kebbi','Kogi','Kwara','Lagos','Nasarawa','Niger','Ogun','Ondo','Osun','Oyo','Plateau','Rivers','Sokoto','Taraba','Yobe','Zamfara','FCT',
            ],
            'topByState' => (function(){
                $rows = DB::table('votes as v')
                    ->join('voter_verifications as vv', function($join){
                        $join->on('vv.user_id','=','v.user_id')->on('vv.election_id','=','v.election_id');
                    })
                    ->select('vv.state','v.candidate_id', DB::raw('count(*) as count'))
                    ->where('v.election_id', $this->election->id)
                    ->where('vv.status','approved')
                    ->groupBy('vv.state','v.candidate_id')
                    ->get();
                $map = [];
                foreach ($rows as $r) {
                    $s = $r->state ?? '';
                    if (! isset($map[$s]) || (int)$r->count > ($map[$s]['count'] ?? 0)) {
                        $map[$s] = ['candidate_id' => $r->candidate_id, 'count' => (int)$r->count];
                    }
                }
                return $map;
            })(),
            'candidateMap' => $this->election->candidates()->get()->keyBy('id'),
            'isEnded' => now()->gt($this->election->end_at),
            'winner' => (function(){
                $total = DB::table('votes')->where('election_id', $this->election->id)->count();
                $top = DB::table('votes')
                    ->select('candidate_id', DB::raw('count(*) as count'))
                    ->where('election_id', $this->election->id)
                    ->groupBy('candidate_id')
                    ->orderByDesc('count')
                    ->first();
                if (! $top) return null;
                $c = Candidate::find($top->candidate_id);
                return [
                    'candidate' => $c,
                    'count' => (int) $top->count,
                    'percent' => $total > 0 ? intval(($top->count / $total) * 100) : 0,
                ];
            })(),
        ];
    }

    public function ensureTwoRows(): void
    {
        if (count($this->newCandidates) < 2) {
            $needed = 2 - count($this->newCandidates);
            for ($i = 0; $i < $needed; $i++) {
                $this->newCandidates[] = [
                    'name' => '',
                    'party' => '',
                    'bio' => '',
                    'photo' => null,
                ];
            }
        }
    }

    public function addRow(): void
    {
        $this->newCandidates[] = [
            'name' => '',
            'party' => '',
            'bio' => '',
            'photo' => null,
        ];
    }

    public function removeRow(int $index): void
    {
        if (isset($this->newCandidates[$index])) {
            unset($this->newCandidates[$index]);
            $this->newCandidates = array_values($this->newCandidates);
            $this->ensureTwoRows();
        }
    }

    public function saveCandidates(): void
    {
        $rules = [];
        foreach ($this->newCandidates as $i => $candidate) {
            $rules["newCandidates.$i.name"] = ['required', 'string', 'max:255'];
            $rules["newCandidates.$i.party"] = ['nullable', 'string', 'max:255'];
            $rules["newCandidates.$i.bio"] = ['nullable', 'string'];
            $rules["newCandidates.$i.photo"] = ['nullable', 'image', 'max:2048'];
        }
        $this->validate($rules);

        foreach ($this->newCandidates as $candidate) {
            if (trim($candidate['name']) === '') {
                continue;
            }

            $path = null;
            if (! empty($candidate['photo'])) {
                $path = $candidate['photo']->store('candidates', 'public');
            }

            Candidate::create([
                'election_id' => $this->election->id,
                'name' => $candidate['name'],
                'party' => $candidate['party'] ?: null,
                'bio' => $candidate['bio'] ?: null,
                'photo_path' => $path,
            ]);
        }

        $this->newCandidates = [];
        $this->ensureTwoRows();
        session()->flash('status', 'Candidates added successfully.');
    }

    public function openCandidateEdit(int $id): void
    {
        $c = Candidate::findOrFail($id);
        $this->editingCandidateId = $id;
        $this->edit_candidate_name = $c->name;
        $this->edit_candidate_party = $c->party ?? '';
        $this->edit_candidate_bio = $c->bio ?? '';
        $this->edit_candidate_photo = null;
        $this->showEditCandidateModal = true;
    }

    public function closeCandidateEdit(): void
    {
        $this->showEditCandidateModal = false;
        $this->editingCandidateId = null;
        $this->reset('edit_candidate_name', 'edit_candidate_party', 'edit_candidate_bio', 'edit_candidate_photo');
    }

    public function updateCandidate(): void
    {
        if (! $this->editingCandidateId) {
            return;
        }

        $this->validate([
            'edit_candidate_name' => ['required', 'string', 'max:255'],
            'edit_candidate_party' => ['nullable', 'string', 'max:255'],
            'edit_candidate_bio' => ['nullable', 'string'],
            'edit_candidate_photo' => ['nullable', 'image', 'max:2048'],
        ]);

        $c = Candidate::findOrFail($this->editingCandidateId);
        $path = $c->photo_path;
        if (! empty($this->edit_candidate_photo)) {
            $path = $this->edit_candidate_photo->store('candidates', 'public');
        }
        $c->update([
            'name' => $this->edit_candidate_name,
            'party' => $this->edit_candidate_party ?: null,
            'bio' => $this->edit_candidate_bio ?: null,
            'photo_path' => $path,
        ]);

        $this->closeCandidateEdit();
        session()->flash('status', 'Candidate updated successfully.');
    }
};
?>

<main>
    <section class="w-full">
        <flux:heading size="xl" level="1">{{ $election->title }}</flux:heading>
        <flux:subheading size="lg" class="mt-1">{{ __('Election Details') }}</flux:subheading>

        @if ($election->banner_path)
            <div class="mt-6 w-full max-w-4xl relative">
                <img src="{{ asset('storage/'.$election->banner_path) }}" alt="Banner" class="w-full h-48 md:h-64 lg:h-72 object-cover rounded-xl" />
                <div class="absolute inset-0 rounded-xl bg-black/30"></div>
                <div class="absolute inset-0 flex flex-col justify-end p-6">
                    <div class="flex items-center gap-3">
                        <span class="inline-flex items-center rounded-full bg-white/90 text-black text-xs font-semibold px-3 py-1 dark:bg-black/60 dark:text-white">
                            @if ($election->is_paused)
                                {{ __('Paused') }}
                            @elseif (now()->lt($election->start_at))
                                {{ __('Scheduled') }}
                            @elseif (now()->gt($election->end_at))
                                {{ __('Ended') }}
                            @else
                                {{ __('Ongoing') }}
                            @endif
                        </span>
                        <span class="text-white/95 text-xs md:text-sm">
                            {{ $election->start_at->format('M j, Y g:i A') }} — {{ $election->end_at->format('M j, Y g:i A') }}
                        </span>
                    </div>
                    <div class="mt-2">
                        <div class="text-white text-2xl md:text-3xl font-semibold">{{ $election->title }}</div>
                    </div>
                </div>
            </div>
        @endif

        <div class="mt-6 w-full max-w-4xl">
            <div class="rounded-xl border bg-white dark:bg-zinc-900 dark:border-zinc-800 shadow-xs">
                <div class="px-6 py-6 grid gap-4 md:grid-cols-2">
                    <div>
                        <flux:heading size="md">{{ __('Description') }}</flux:heading>
                        <flux:text>{{ $election->description }}</flux:text>
                    </div>
                    <div>
                        <flux:heading size="md">{{ __('Window') }}</flux:heading>
                        <flux:text>{{ $election->start_at->format('M j, Y g:i A') }} — {{ $election->end_at->format('M j, Y g:i A') }}</flux:text>
                    </div>
                    <div>
                        <flux:heading size="md">{{ __('Status') }}</flux:heading>
                        <flux:text>
                            @if ($election->is_paused)
                                {{ __('Paused') }}
                            @elseif (now()->lt($election->start_at))
                                {{ __('Scheduled') }}
                            @elseif (now()->gt($election->end_at))
                                {{ __('Ended') }}
                            @else
                                {{ __('Ongoing') }}
                            @endif
                        </flux:text>
                    </div>
                    
                </div>
                </div>

                @if ($isEnded && $winner)
                    <div class="mt-8 rounded-xl border bg-white dark:bg-zinc-900 dark:border-zinc-800 shadow-xs p-6">
                        <flux:heading size="lg">{{ __('Winner') }}</flux:heading>
                        <div class="mt-3 flex items-center gap-4">
                            @if ($winner['candidate']?->photo_path)
                                <img src="{{ asset('storage/'.$winner['candidate']->photo_path) }}" alt="{{ $winner['candidate']->name }}" class="h-16 w-16 rounded object-cover" />
                            @else
                                <div class="h-16 w-16 rounded bg-amber-100 dark:bg-amber-900/40 flex items-center justify-center text-lg font-semibold">
                                    {{ strtoupper(Str::substr($winner['candidate']?->name ?? '', 0, 1)) }}
                                </div>
                            @endif
                            <div>
                                <div class="text-base font-semibold">{{ $winner['candidate']?->name }}</div>
                                @if ($winner['candidate']?->party)
                                    <div class="text-sm text-zinc-500">{{ $winner['candidate']->party }}</div>
                                @endif
                            </div>
                            <flux:spacer />
                            <div class="text-2xl font-bold">{{ $winner['count'] }} <span class="text-sm text-zinc-500">({{ $winner['percent'] }}%)</span></div>
                        </div>
                        <div class="mt-4">
                            <flux:callout variant="success" icon="trophy" heading="{{ __('Congratulations to') }} {{ $winner['candidate']?->name }}!" />
                        </div>
                    </div>
                @endif

                <div class="mt-10">
                    <flux:heading size="lg" level="2">{{ __('Candidates') }}</flux:heading>
                    @if (session('status'))
                        <flux:callout variant="success" icon="check-circle" heading="{{ session('status') }}" />
                    @endif

                <div class="mt-4 grid gap-4 md:grid-cols-3">
                    @foreach ($candidates as $c)
                        <div class="rounded-xl border bg-white dark:bg-zinc-900 dark:border-zinc-800 shadow-xs p-4">
                            <div class="flex items-center gap-3">
                                @if ($c->photo_path)
                                    <img src="{{ asset('storage/'.$c->photo_path) }}" alt="{{ $c->name }}" class="h-12 w-12 rounded object-cover" />
                                @else
                                    <div class="h-12 w-12 rounded bg-zinc-200 dark:bg-zinc-700 flex items-center justify-center text-sm font-semibold">
                                        {{ strtoupper(Str::substr($c->name, 0, 1)) }}
                                    </div>
                                @endif
                                <div>
                                    <div class="text-sm font-semibold">{{ $c->name }}</div>
                                    @if ($c->party)
                                        <div class="text-xs text-zinc-500">{{ $c->party }}</div>
                                    @endif
                                </div>
                                <flux:spacer />
                                <div class="text-lg font-bold">{{ $voteCounts[$c->id] ?? 0 }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- <div class="mt-10">
                    <flux:heading size="lg" level="2">{{ __('Highest Votes by State') }}</flux:heading>
                    <div class="mt-4 grid gap-3 md:grid-cols-3">
                        @foreach ($states as $s)
                            @php($top = $topByState[$s] ?? null)
                            <div class="rounded-xl border bg-white dark:bg-zinc-900 dark:border-zinc-800 shadow-xs p-4 flex items-center gap-3">
                                <div class="h-12 w-12 rounded bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center overflow-hidden">
                                    @if ($top && ($candidateMap[$top['candidate_id']]->photo_path ?? null))
                                        <img src="{{ asset('storage/'.$candidateMap[$top['candidate_id']]->photo_path) }}" alt="{{ $candidateMap[$top['candidate_id']]->name }}" class="h-12 w-12 object-cover" />
                                    @else
                                        <span class="text-xs font-semibold">{{ $s }}</span>
                                    @endif
                                </div>
                                <div>
                                    <div class="text-sm font-semibold">{{ $s }}</div>
                                    <div class="text-xs text-zinc-500">
                                        @if ($top)
                                            {{ $candidateMap[$top['candidate_id']]->name }}
                                        @else
                                            {{ __('No votes') }}
                                        @endif
                                    </div>
                                </div>
                                <flux:spacer />
                                <div class="text-lg font-bold">{{ $top['count'] ?? 0 }}</div>
                            </div>
                        @endforeach
                    </div>
                </div> --}}

                <div class="mt-10">
                    <flux:heading size="lg" level="2">{{ __('Highest Votes by State') }}</flux:heading>
                    <div class="mt-4 grid gap-3 md:grid-cols-3">
                        @foreach ($states as $s)
                            @php($top = $topByState[$s] ?? null)
                            <div class="rounded-xl border bg-white dark:bg-zinc-900 dark:border-zinc-800 shadow-xs p-4 flex items-center gap-3">
                                <div class="h-12 w-12 rounded bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center overflow-hidden">
                                    @if ($top && ($candidateMap[$top['candidate_id']]->photo_path ?? null))
                                        <img src="{{ asset('storage/'.$candidateMap[$top['candidate_id']]->photo_path) }}" alt="{{ $candidateMap[$top['candidate_id']]->name }}" class="h-12 w-12 object-cover" />
                                    @else
                                        <span class="text-xs font-semibold">{{ $s }}</span>
                                    @endif
                                </div>
                                <div>
                                    <div class="text-sm font-semibold">{{ $s }}</div>
                                    <div class="text-xs text-zinc-500">
                                        @if ($top)
                                            {{ $candidateMap[$top['candidate_id']]->name }}
                                        @else
                                            {{ __('No votes') }}
                                        @endif
                                    </div>
                                </div>
                                <flux:spacer />
                                <div class="text-lg font-bold">{{ $top['count'] ?? 0 }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="mt-4 rounded-xl border bg-white dark:bg-zinc-900 dark:border-zinc-800 shadow-xs">
                    <div class="px-6 py-6">
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead>
                                    <tr class="text-left">
                                        <th class="py-2 pe-4">{{ __('Name') }}</th>
                                        <th class="py-2 pe-4">{{ __('Party') }}</th>
                                        <th class="py-2 pe-4">{{ __('Bio') }}</th>
                                        <th class="py-2 pe-4">{{ __('Photo') }}</th>
                                        <th class="py-2">{{ __('Actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($candidates as $c)
                                        <tr class="border-t dark:border-zinc-800">
                                            <td class="py-2 pe-4">{{ $c->name }}</td>
                                            <td class="py-2 pe-4">{{ $c->party }}</td>
                                            <td class="py-2 pe-4">{{ Str::limit($c->bio, 100) }}</td>
                                            <td class="py-2">
                                                @if ($c->photo_path)
                                                    <img src="{{ asset('storage/'.$c->photo_path) }}" alt="{{ $c->name }}" class="h-10 w-10 rounded object-cover" />
                                                @else
                                                    —
                                                @endif
                                            </td>
                                            <td class="py-2">
                                                <flux:button size="sm" variant="primary" color="amber" wire:click="openCandidateEdit({{ $c->id }})">{{ __('Edit') }}</flux:button>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td class="py-4" colspan="4">{{ __('No candidates yet.') }}</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="mt-6 rounded-xl border bg-white dark:bg-zinc-900 dark:border-zinc-800 shadow-xs">
                    <div class="px-6 py-6">
                        <form wire:submit="saveCandidates" class="flex flex-col gap-6">
                            <div class="flex items-center gap-3">
                                <flux:heading size="md">{{ __('Add Candidates') }}</flux:heading>
                                <flux:spacer />
                                <flux:button type="button" variant="ghost" icon="plus" wire:click="addRow">{{ __('Add More') }}</flux:button>
                            </div>
                            <div class="grid gap-4">
                                @foreach ($newCandidates as $index => $nc)
                                    <div class="grid gap-3 md:grid-cols-4 items-end">
                                        <flux:input wire:model="newCandidates.{{ $index }}.name" :label="__('Name')" type="text" />
                                        <flux:input wire:model="newCandidates.{{ $index }}.party" :label="__('Party')" type="text" />
                                        <flux:textarea wire:model="newCandidates.{{ $index }}.bio" :label="__('Bio')" rows="2" />
                                        <div>
                                            <label class="block text-sm font-medium">{{ __('Photo') }}</label>
                                            <input type="file" wire:model="newCandidates.{{ $index }}.photo" class="mt-2 block w-full text-sm" />
                                        </div>
                                        <div class="md:col-span-4 flex justify-end">
                                            <flux:button type="button" size="sm" variant="ghost" icon="trash" wire:click="removeRow({{ $index }})">{{ __('Remove') }}</flux:button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <div class="flex items-center gap-3">
                                <flux:spacer />
                                <flux:button type="submit" variant="primary" color='green' icon="check">{{ __('Save Candidates') }}</flux:button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <flux:modal name="edit-candidate-modal" class="max-w-2xl" wire:model="showEditCandidateModal">
        <div class="space-y-6">
            <flux:heading size="lg">{{ __('Edit Candidate') }}</flux:heading>
            <div class="grid gap-4 md:grid-cols-2">
                <flux:input wire:model="edit_candidate_name" :label="__('Name')" type="text" />
                <flux:input wire:model="edit_candidate_party" :label="__('Party')" type="text" />
                <div class="md:col-span-2">
                    <flux:textarea wire:model="edit_candidate_bio" :label="__('Bio')" rows="4" />
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium">{{ __('Photo') }}</label>
                    <input type="file" wire:model="edit_candidate_photo" class="mt-2 block w-full text-sm" />
                </div>
            </div>
            <div class="flex items-center gap-3">
                <flux:button variant="ghost" wire:click="closeCandidateEdit">{{ __('Cancel') }}</flux:button>
                <flux:spacer />
                <flux:button variant="primary" icon="check" wire:click="updateCandidate">{{ __('Save') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</main>