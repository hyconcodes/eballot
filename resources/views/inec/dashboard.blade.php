<x-layouts.app :title="__('INEC Officers Dashboard')">
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        @php($elections = \App\Models\Election::where('type','presidential')->where('start_at','<=',now())->where('end_at','>=',now())->where('is_paused',false)->orderBy('start_at')->get())
        @php($electionIds = $elections->pluck('id'))
        @php($pendingCount = \Illuminate\Support\Facades\DB::table('voter_verifications')->whereIn('election_id',$electionIds)->where('status','pending')->count())
        @php($approvedCount = \Illuminate\Support\Facades\DB::table('voter_verifications')->whereIn('election_id',$electionIds)->where('status','approved')->count())
        @php($rejectedCount = \Illuminate\Support\Facades\DB::table('voter_verifications')->whereIn('election_id',$electionIds)->where('status','rejected')->count())
        @php($votesTotal = \Illuminate\Support\Facades\DB::table('votes')->whereIn('election_id',$electionIds)->count())
        <div class="grid gap-4 md:grid-cols-4">
            <div class="rounded-xl border bg-white dark:bg-zinc-900 dark:border-zinc-800 p-4">
                <div class="text-sm text-zinc-500">{{ __('Pending Verifications') }}</div>
                <div class="mt-1 text-2xl font-semibold">{{ $pendingCount }}</div>
            </div>
            <div class="rounded-xl border bg-white dark:bg-zinc-900 dark:border-zinc-800 p-4">
                <div class="text-sm text-zinc-500">{{ __('Approved') }}</div>
                <div class="mt-1 text-2xl font-semibold">{{ $approvedCount }}</div>
            </div>
            <div class="rounded-xl border bg-white dark:bg-zinc-900 dark:border-zinc-800 p-4">
                <div class="text-sm text-zinc-500">{{ __('Rejected') }}</div>
                <div class="mt-1 text-2xl font-semibold">{{ $rejectedCount }}</div>
            </div>
            <div class="rounded-xl border bg-white dark:bg-zinc-900 dark:border-zinc-800 p-4">
                <div class="text-sm text-zinc-500">{{ __('Votes Cast') }}</div>
                <div class="mt-1 text-2xl font-semibold">{{ $votesTotal }}</div>
            </div>
        </div>

        @php($pending = \App\Models\VoterVerification::whereIn('election_id',$electionIds)->where('status','pending')->orderBy('created_at')->limit(8)->get())
        @php($recent = \App\Models\VoterVerification::whereIn('election_id',$electionIds)->whereIn('status',['approved','rejected'])->orderByDesc('updated_at')->limit(8)->get())

        <div class="mt-6 grid gap-6 md:grid-cols-2">
            <div class="rounded-xl border bg-white dark:bg-zinc-900 dark:border-zinc-800 p-6">
                <div class="flex items-center justify-between">
                    <flux:heading size="md">{{ __('Pending Reviews') }}</flux:heading>
                    <flux:button size="sm" variant="primary" :href="route('inec.verification')" wire:navigate>{{ __('Open Verification') }}</flux:button>
                </div>
                <div class="mt-4 overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left">
                                <th class="py-2 pe-4">{{ __('Voter') }}</th>
                                <th class="py-2 pe-4">{{ __('Election') }}</th>
                                <th class="py-2 pe-4">{{ __('NIN') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($pending as $v)
                                <tr class="border-t dark:border-zinc-800">
                                    <td class="py-2 pe-4">{{ $v->user->name }}</td>
                                    <td class="py-2 pe-4">{{ $v->election->title }}</td>
                                    <td class="py-2 pe-4">{{ $v->nin_number }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="py-4" colspan="3">{{ __('No pending verifications.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="rounded-xl border bg-white dark:bg-zinc-900 dark:border-zinc-800 p-6">
                <flux:heading size="md">{{ __('Recent Decisions') }}</flux:heading>
                <div class="mt-4 overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left">
                                <th class="py-2 pe-4">{{ __('Voter') }}</th>
                                <th class="py-2 pe-4">{{ __('Election') }}</th>
                                <th class="py-2 pe-4">{{ __('Status') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recent as $v)
                                <tr class="border-t dark:border-zinc-800">
                                    <td class="py-2 pe-4">{{ $v->user->name }}</td>
                                    <td class="py-2 pe-4">{{ $v->election->title }}</td>
                                    <td class="py-2 pe-4">{{ ucfirst($v->status) }}</td>
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

        @php($states = ['Abia','Adamawa','Akwa Ibom','Anambra','Bauchi','Bayelsa','Benue','Borno','Cross River','Delta','Ebonyi','Edo','Ekiti','Enugu','Gombe','Imo','Jigawa','Kaduna','Kano','Katsina','Kebbi','Kogi','Kwara','Lagos','Nasarawa','Niger','Ogun','Ondo','Osun','Oyo','Plateau','Rivers','Sokoto','Taraba','Yobe','Zamfara','FCT'])
        @if ($elections->count())
            <div class="rounded-xl border bg-white dark:bg-zinc-900 dark:border-zinc-800 shadow-xs p-6">
                <flux:heading size="lg">{{ __('Election Results by State') }}</flux:heading>
                <div class="mt-4 space-y-6">
                    @foreach ($elections as $election)
                        @php($rows = \Illuminate\Support\Facades\DB::table('votes as v')->join('voter_verifications as vv', function($join){$join->on('vv.user_id','=','v.user_id')->on('vv.election_id','=','v.election_id');})->select('vv.state','v.candidate_id', \Illuminate\Support\Facades\DB::raw('count(*) as count'))->where('v.election_id', $election->id)->where('vv.status','approved')->groupBy('vv.state','v.candidate_id')->get())
                        @php($map = [])
                        @foreach ($rows as $r)
                            @php($map[$r->state ?? ''] = ($map[$r->state ?? ''] ?? []))
                            @php($map[$r->state ?? ''][$r->candidate_id] = (int) $r->count)
                        @endforeach
                        <div>
                            <flux:heading size="md">{{ $election->title }}</flux:heading>
                            <div class="mt-2 grid gap-2 md:grid-cols-3">
                                @foreach ($states as $s)
                                    <div class="rounded-md bg-zinc-50 dark:bg-zinc-800 px-3 py-2">
                                        <div class="text-sm font-semibold">{{ $s }}</div>
                                        <div class="mt-2 space-y-1">
                                            @foreach ($election->candidates as $candidate)
                                                <div class="flex items-center justify-between">
                                                    <div class="text-xs">{{ $candidate->name }}</div>
                                                    <div class="text-xs font-semibold">{{ $map[$s][$candidate->id] ?? 0 }}</div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</x-layouts.app>