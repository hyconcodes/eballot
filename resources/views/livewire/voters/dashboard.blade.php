<?php

use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use App\Models\Election;
use App\Models\Candidate;
use App\Models\VoterVerification;
use App\Models\Vote;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

new class extends Component {
    use WithFileUploads;

    public string $nin_number = '';
    public $nin_front = null;
    public $nin_back = null;
    public $card_front = null;
    public $card_back = null;

    public ?string $verificationStatus = null;
    public ?int $verificationId = null;
    public bool $showVoteConfirmModal = false;
    public ?int $voteElectionId = null;
    public ?int $voteCandidateId = null;
    public ?int $viewElectionId = null;

    public function mount(): void
    {
        abort_unless(auth()->user()?->hasRole('voters'), 403);
        $this->viewElectionId = Election::where('type','presidential')->orderByDesc('start_at')->value('id');
    }

    public function with(): array
    {
        $elections = Election::where('type', 'presidential')
            ->where('start_at', '<=', now())
            ->where('end_at', '>=', now())
            ->where('is_paused', false)
            ->orderBy('start_at')
            ->get();

        $allElections = Election::where('type','presidential')->orderByDesc('start_at')->get();
        $selected = $this->viewElectionId ? Election::find($this->viewElectionId) : ($allElections->first() ?: null);
        $selectedCounts = $selected ? $this->resultsForElection($selected->id) : [];
        $totalVotes = $selected ? DB::table('votes')->where('election_id', $selected->id)->count() : 0;
        $top = $selected ? DB::table('votes')
            ->select('candidate_id', DB::raw('count(*) as count'))
            ->where('election_id', $selected->id)
            ->groupBy('candidate_id')
            ->orderByDesc('count')
            ->first() : null;
        $winner = null;
        if ($selected && $top) {
            $c = Candidate::find($top->candidate_id);
            $winner = [
                'candidate' => $c,
                'count' => (int) $top->count,
                'percent' => $totalVotes > 0 ? intval(($top->count / $totalVotes) * 100) : 0,
                'ended' => now()->gt($selected->end_at),
            ];
        }

        $myVotes = Vote::where('user_id', Auth::id())->get()->keyBy('election_id');
        $verifications = VoterVerification::where('user_id', Auth::id())
            ->whereIn('election_id', $elections->pluck('id'))
            ->get()
            ->keyBy('election_id');

        return [
            'elections' => $elections,
            'myVotes' => $myVotes,
            'verifications' => $verifications,
            'states' => [
                'Abia','Adamawa','Akwa Ibom','Anambra','Bauchi','Bayelsa','Benue','Borno','Cross River','Delta','Ebonyi','Edo','Ekiti','Enugu','Gombe','Imo','Jigawa','Kaduna','Kano','Katsina','Kebbi','Kogi','Kwara','Lagos','Nasarawa','Niger','Ogun','Ondo','Osun','Oyo','Plateau','Rivers','Sokoto','Taraba','Yobe','Zamfara','FCT',
            ],
            'allElections' => $allElections,
            'selectedElection' => $selected,
            'selectedCounts' => $selectedCounts,
            'selectedWinner' => $winner,
        ];
    }

    public function submitVerification(int $electionId): void
    {
        $this->validate([
            'nin_number' => ['required', 'string', 'max:32'],
            'nin_front' => ['required', 'image', 'max:4096'],
            'nin_back' => ['required', 'image', 'max:4096'],
            'card_front' => ['required', 'image', 'max:4096'],
            'card_back' => ['required', 'image', 'max:4096'],
        ]);

        $paths = [
            'nin_front_path' => $this->nin_front->store('verifications', 'public'),
            'nin_back_path' => $this->nin_back->store('verifications', 'public'),
            'voters_card_front_path' => $this->card_front->store('verifications', 'public'),
            'voters_card_back_path' => $this->card_back->store('verifications', 'public'),
        ];

        $v = VoterVerification::updateOrCreate(
            ['user_id' => Auth::id(), 'election_id' => $electionId],
            array_merge([
                'nin_number' => $this->nin_number,
                'status' => 'pending',
                'verified_by' => null,
                'verified_at' => null,
                'notes' => null,
            ], $paths)
        );

        session()->flash('status', 'Verification submitted. Awaiting approval.');
        $this->reset('nin_front', 'nin_back', 'card_front', 'card_back');
    }

    public function castVote(int $electionId, int $candidateId): void
    {
        $isApproved = VoterVerification::where('user_id', Auth::id())
            ->where('election_id', $electionId)
            ->where('status', 'approved')
            ->exists();
        if (! $isApproved) {
            session()->flash('warning', 'Verification required before voting.');
            return;
        }

        $exists = Vote::where('user_id', Auth::id())->where('election_id', $electionId)->exists();
        if ($exists) {
            session()->flash('warning', 'You have already voted in this election.');
            return;
        }

        $e = Election::findOrFail($electionId);
        if (! ($e->start_at <= now() && $e->end_at >= now()) || $e->is_paused) {
            session()->flash('warning', 'Voting is not available for this election.');
            return;
        }

        Vote::create([
            'user_id' => Auth::id(),
            'election_id' => $electionId,
            'candidate_id' => $candidateId,
        ]);

        session()->flash('status', 'Vote recorded successfully.');
    }

    public function resultsForElection(int $electionId): array
    {
        $rows = DB::table('votes')
            ->select('candidate_id', DB::raw('count(*) as count'))
            ->where('election_id', $electionId)
            ->groupBy('candidate_id')
            ->get();
        $map = [];
        foreach ($rows as $r) {
            $map[$r->candidate_id] = (int) $r->count;
        }
        return $map;
    }

    public function stateResultsForElection(int $electionId): array
    {
        $rows = DB::table('votes as v')
            ->join('voter_verifications as vv', function ($join) {
                $join->on('vv.user_id', '=', 'v.user_id')
                     ->on('vv.election_id', '=', 'v.election_id');
            })
            ->select('vv.state', DB::raw('count(*) as count'))
            ->where('v.election_id', $electionId)
            ->where('vv.status', 'approved')
            ->groupBy('vv.state')
            ->get();
        $map = [];
        foreach ($rows as $r) {
            $map[$r->state ?? ''] = (int) $r->count;
        }
        return $map;
    }

    public function stateCandidateResultsForElection(int $electionId): array
    {
        $rows = DB::table('votes as v')
            ->join('voter_verifications as vv', function ($join) {
                $join->on('vv.user_id', '=', 'v.user_id')
                     ->on('vv.election_id', '=', 'v.election_id');
            })
            ->select('vv.state', 'v.candidate_id', DB::raw('count(*) as count'))
            ->where('v.election_id', $electionId)
            ->where('vv.status', 'approved')
            ->groupBy('vv.state', 'v.candidate_id')
            ->get();
        $map = [];
        foreach ($rows as $r) {
            $state = $r->state ?? '';
            if (! isset($map[$state])) {
                $map[$state] = [];
            }
            $map[$state][$r->candidate_id] = (int) $r->count;
        }
        return $map;
    }

    public function openVoteConfirm(int $electionId, int $candidateId): void
    {
        $this->voteElectionId = $electionId;
        $this->voteCandidateId = $candidateId;
        $this->showVoteConfirmModal = true;
    }

    public function closeVoteConfirm(): void
    {
        $this->showVoteConfirmModal = false;
        $this->voteElectionId = null;
        $this->voteCandidateId = null;
    }

    public function performVote(): void
    {
        if (! $this->voteElectionId || ! $this->voteCandidateId) {
            return;
        }
        $this->castVote($this->voteElectionId, $this->voteCandidateId);
        $this->closeVoteConfirm();
    }
};
?>
<main>
    <div class="space-y-6">
        @if (session('status'))
            <flux:callout variant="success" icon="check-circle" heading="{{ session('status') }}" />
        @endif
        @if (session('warning'))
            <flux:callout variant="warning" icon="exclamation-triangle" heading="{{ session('warning') }}" />
        @endif

        <div class="rounded-xl border bg-white dark:bg-zinc-900 dark:border-zinc-800 shadow-xs">
            <div class="px-6 py-6">
                <flux:heading size="lg">{{ __('Election Viewer') }}</flux:heading>
                <div class="mt-4 grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium">{{ __('Select Election') }}</label>
                        <select wire:model="viewElectionId" class="mt-2 block w-full rounded border dark:border-zinc-700 bg-white dark:bg-zinc-900 p-2 text-sm">
                            @foreach ($allElections as $el)
                                <option value="{{ $el->id }}">{{ $el->title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex items-end justify-end">
                        @if ($selectedElection)
                            @if (now()->lt($selectedElection->start_at))
                                <flux:badge variant="warning">{{ __('Scheduled') }}</flux:badge>
                            @elseif (now()->gt($selectedElection->end_at))
                                <flux:badge variant="gray">{{ __('Ended') }}</flux:badge>
                            @else
                                <flux:badge variant="success">{{ __('Ongoing') }}</flux:badge>
                            @endif
                        @endif
                    </div>
                </div>
                @if ($selectedElection)
                    <div class="mt-4 grid gap-4 md:grid-cols-2">
                        <div>
                            <flux:heading size="sm">{{ __('Window') }}</flux:heading>
                            <flux:text>{{ $selectedElection->start_at->format('M j, Y g:i A') }} — {{ $selectedElection->end_at->format('M j, Y g:i A') }}</flux:text>
                        </div>
                        <div>
                            <flux:heading size="sm">{{ __('Description') }}</flux:heading>
                            <flux:text>{{ $selectedElection->description }}</flux:text>
                        </div>
                    </div>
                    <div class="mt-4 grid gap-3 md:grid-cols-3">
                        @foreach ($selectedElection->candidates as $c)
                            <div class="rounded-md border dark:border-zinc-800 p-3 flex items-center gap-3">
                                @if ($c->photo_path)
                                    <img src="{{ asset('storage/'.$c->photo_path) }}" alt="{{ $c->name }}" class="h-10 w-10 rounded object-cover" />
                                @endif
                                <div>
                                    <div class="text-sm font-semibold">{{ $c->name }}</div>
                                    @if ($c->party)
                                        <div class="text-xs text-zinc-500">{{ $c->party }}</div>
                                    @endif
                                </div>
                                <flux:spacer />
                                <div class="text-lg font-bold">{{ $selectedCounts[$c->id] ?? 0 }}</div>
                            </div>
                        @endforeach
                    </div>
                    @if ($selectedWinner && $selectedWinner['ended'])
                        <div class="mt-6 rounded-md border dark:border-zinc-800 p-4">
                            <flux:heading size="md">{{ __('Winner') }}</flux:heading>
                            <div class="mt-3 flex items-center gap-4">
                                @if ($selectedWinner['candidate']?->photo_path)
                                    <img src="{{ asset('storage/'.$selectedWinner['candidate']->photo_path) }}" alt="{{ $selectedWinner['candidate']->name }}" class="h-12 w-12 rounded object-cover" />
                                @endif
                                <div>
                                    <div class="text-sm font-semibold">{{ $selectedWinner['candidate']?->name }}</div>
                                    @if ($selectedWinner['candidate']?->party)
                                        <div class="text-xs text-zinc-500">{{ $selectedWinner['candidate']->party }}</div>
                                    @endif
                                </div>
                                <flux:spacer />
                                <div class="text-xl font-bold">{{ $selectedWinner['count'] }} <span class="text-xs text-zinc-500">({{ $selectedWinner['percent'] }}%)</span></div>
                            </div>
                            <div class="mt-3">
                                <flux:callout variant="success" icon="trophy" heading="{{ __('Congratulations to') }} {{ $selectedWinner['candidate']?->name }}!" />
                            </div>
                        </div>
                    @endif
                @endif
            </div>
        </div>

        <div class="rounded-xl border bg-white dark:bg-zinc-900 dark:border-zinc-800 shadow-xs">
            <div class="px-6 py-6">
                <flux:heading size="lg">{{ __('Ongoing Elections') }}</flux:heading>
                <div class="mt-4 space-y-6">
                    @forelse ($elections as $election)
                        <div class="rounded-lg border dark:border-zinc-800 p-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <flux:heading size="md">{{ $election->title }}</flux:heading>
                                    <flux:text>{{ $election->start_at->format('M j, Y g:i A') }} — {{ $election->end_at->format('M j, Y g:i A') }}</flux:text>
                                </div>
                                <div>
                                    @php($v = $verifications[$election->id] ?? null)
                                    @if ($v && $v->status === 'approved')
                                        <flux:badge variant="success">{{ __('Approved') }}</flux:badge>
                                    @elseif ($v && $v->status === 'pending')
                                        <flux:badge variant="warning">{{ __('Pending') }}</flux:badge>
                                    @elseif ($v && $v->status === 'rejected')
                                        <flux:badge variant="danger">{{ __('Rejected') }}</flux:badge>
                                    @else
                                        <flux:badge variant="primary">{{ __('Verification Required') }}</flux:badge>
                                    @endif
                                </div>
                            </div>
                            <div class="mt-3 grid gap-2 md:grid-cols-3">
                                @foreach ($election->candidates as $candidate)
                                    <div class="flex items-center justify-between rounded-md border dark:border-zinc-800 px-3 py-2">
                                        <div class="flex items-center gap-3">
                                            @if ($candidate->photo_path)
                                                <img src="{{ asset('storage/'.$candidate->photo_path) }}" alt="{{ $candidate->name }}" class="h-8 w-8 rounded object-cover" />
                                            @endif
                                            <div>
                                                <div class="text-sm font-medium">{{ $candidate->name }}</div>
                                                <div class="text-xs text-zinc-500">{{ $candidate->party }}</div>
                                            </div>
                                        </div>
                                        <div>
                                            @if (($verifications[$election->id]->status ?? null) === 'approved')
                                                @if (! isset($myVotes[$election->id]))
                                                    <flux:button size="sm" variant="primary" wire:click="openVoteConfirm({{ $election->id }}, {{ $candidate->id }})">{{ __('Vote') }}</flux:button>
                                                @else
                                                    <flux:button size="sm" variant="ghost" disabled>{{ __('Voted') }}</flux:button>
                                                @endif
                                            @else
                                                <flux:button size="sm" variant="ghost" disabled>{{ __('Verify First') }}</flux:button>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            @if (($verifications[$election->id]->status ?? null) === 'rejected')
                                <div class="mt-3">
                                    <flux:callout variant="danger" icon="exclamation-triangle" heading="{{ __('Verification rejected') }}">
                                        @php($v = $verifications[$election->id] ?? null)
                                        @if ($v && $v->notes)
                                            <flux:text>{{ $v->notes }}</flux:text>
                                        @endif
                                    </flux:callout>
                                </div>
                            @endif
                            @if (! (($verifications[$election->id]->status ?? null) === 'approved'))
                                <div class="mt-4">
                                    <flux:heading size="sm">{{ __('Submit Verification for this Election') }}</flux:heading>
                                    <form wire:submit.prevent="submitVerification({{ $election->id }})" class="mt-3 grid gap-4 md:grid-cols-2">
                                        <flux:input wire:model="nin_number" :label="__('NIN Number')" type="text" />
                                        <div>
                                            <label class="block text-sm font-medium">{{ __('NIN Front') }}</label>
                                            <input type="file" wire:model="nin_front" class="mt-2 block w-full text-sm" />
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium">{{ __('NIN Back') }}</label>
                                            <input type="file" wire:model="nin_back" class="mt-2 block w-full text-sm" />
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium">{{ __('Voters Card Front') }}</label>
                                            <input type="file" wire:model="card_front" class="mt-2 block w-full text-sm" />
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium">{{ __('Voters Card Back') }}</label>
                                            <input type="file" wire:model="card_back" class="mt-2 block w-full text-sm" />
                                        </div>
                                        <div class="md:col-span-2 flex items-center gap-3 mt-2">
                                            <flux:spacer />
                                            <flux:button type="submit" variant="primary" icon="check">{{ __('Submit Verification') }}</flux:button>
                                        </div>
                                    </form>
                                </div>
                            @endif
                            <div class="mt-3" wire:poll.5s>
                                @php($counts = $this->resultsForElection($election->id))
                                <div class="grid gap-2 md:grid-cols-3">
                                    @foreach ($election->candidates as $candidate)
                                        <div class="flex items-center justify-between rounded-md bg-zinc-50 dark:bg-zinc-800 px-3 py-2">
                                            <div class="text-sm">{{ $candidate->name }}</div>
                                            <div class="text-sm font-semibold">{{ $counts[$candidate->id] ?? 0 }}</div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            <div class="mt-4" wire:poll.10s>
                                @php($stateCandCounts = $this->stateCandidateResultsForElection($election->id))
                                <flux:heading size="sm">{{ __('Results by State') }}</flux:heading>
                                <div class="mt-2 grid gap-2 md:grid-cols-3">
                                    @foreach ($states as $s)
                                        <div class="rounded-md bg-zinc-50 dark:bg-zinc-800 px-3 py-2">
                                            <div class="text-sm font-semibold">{{ $s }}</div>
                                            <div class="mt-2 space-y-1">
                                                @foreach ($election->candidates as $candidate)
                                                    <div class="flex items-center justify-between">
                                                        <div class="text-xs">{{ $candidate->name }}</div>
                                                        <div class="text-xs font-semibold">{{ $stateCandCounts[$s][$candidate->id] ?? 0 }}</div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @empty
                        <flux:text>{{ __('No ongoing elections at the moment.') }}</flux:text>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
    
    <flux:modal name="vote-confirm" class="max-w-md" wire:model="showVoteConfirmModal">
        @php($c = $voteCandidateId ? App\Models\Candidate::find($voteCandidateId) : null)
        @php($e = $voteElectionId ? App\Models\Election::find($voteElectionId) : null)
        @if ($c && $e)
            <div class="space-y-4">
                <flux:heading size="lg">{{ __('Confirm Your Vote') }}</flux:heading>
                <div class="flex items-center gap-3">
                    @if ($c->photo_path)
                        <img src="{{ asset('storage/'.$c->photo_path) }}" alt="{{ $c->name }}" class="h-10 w-10 rounded object-cover" />
                    @endif
                    <div>
                        <div class="text-sm font-semibold">{{ $c->name }}</div>
                        <div class="text-xs text-zinc-500">{{ $c->party }}</div>
                    </div>
                </div>
                <flux:text>{{ $e->title }} • {{ $e->start_at->format('M j, Y g:i A') }} — {{ $e->end_at->format('M j, Y g:i A') }}</flux:text>
                <div class="flex items-center gap-3">
                    <flux:button variant="ghost" wire:click="closeVoteConfirm">{{ __('Cancel') }}</flux:button>
                    <flux:spacer />
                    <flux:button variant="primary" icon="check" wire:click="performVote">{{ __('Confirm Vote') }}</flux:button>
                </div>
            </div>
        @endif
    </flux:modal>
</main>