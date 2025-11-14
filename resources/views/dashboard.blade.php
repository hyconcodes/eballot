<x-layouts.app :title="__('Dashboard')">
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">

        @php($elections = \App\Models\Election::where('type','presidential')->where('start_at','<=',now())->where('end_at','>=',now())->where('is_paused',false)->orderBy('start_at')->get())
        @php($electionIds = $elections->pluck('id'))
        @php($pendingCount = \Illuminate\Support\Facades\DB::table('voter_verifications')->whereIn('election_id',$electionIds)->where('status','pending')->count())
        @php($approvedCount = \Illuminate\Support\Facades\DB::table('voter_verifications')->whereIn('election_id',$electionIds)->where('status','approved')->count())
        @php($votesTotal = \Illuminate\Support\Facades\DB::table('votes')->whereIn('election_id',$electionIds)->count())
        @php($scheduledCount = \App\Models\Election::where('type','presidential')->where('start_at','>',now())->count())
        @php($endedCount = \App\Models\Election::where('type','presidential')->where('end_at','<',now())->count())
        <div class="mt-6 grid gap-4 md:grid-cols-3">
            <div class="rounded-xl border bg-white dark:bg-zinc-900 dark:border-zinc-800 p-4">
                <div class="text-sm text-zinc-500">{{ __('Ongoing Elections') }}</div>
                <div class="mt-1 text-2xl font-semibold">{{ $elections->count() }}</div>
            </div>
            <div class="rounded-xl border bg-white dark:bg-zinc-900 dark:border-zinc-800 p-4">
                <div class="text-sm text-zinc-500">{{ __('Pending Verifications') }}</div>
                <div class="mt-1 text-2xl font-semibold">{{ $pendingCount }}</div>
            </div>
            <div class="rounded-xl border bg-white dark:bg-zinc-900 dark:border-zinc-800 p-4">
                <div class="text-sm text-zinc-500">{{ __('Approved Verifications') }}</div>
                <div class="mt-1 text-2xl font-semibold">{{ $approvedCount }}</div>
            </div>
            <div class="rounded-xl border bg-white dark:bg-zinc-900 dark:border-zinc-800 p-4">
                <div class="text-sm text-zinc-500">{{ __('Votes Cast') }}</div>
                <div class="mt-1 text-2xl font-semibold">{{ $votesTotal }}</div>
            </div>
            <div class="rounded-xl border bg-white dark:bg-zinc-900 dark:border-zinc-800 p-4">
                <div class="text-sm text-zinc-500">{{ __('Scheduled Elections') }}</div>
                <div class="mt-1 text-2xl font-semibold">{{ $scheduledCount }}</div>
            </div>
            <div class="rounded-xl border bg-white dark:bg-zinc-900 dark:border-zinc-800 p-4">
                <div class="text-sm text-zinc-500">{{ __('Ended Elections') }}</div>
                <div class="mt-1 text-2xl font-semibold">{{ $endedCount }}</div>
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
            <div class="mt-6 rounded-xl border bg-white dark:bg-zinc-900 dark:border-zinc-800 shadow-xs p-6">
                <flux:heading size="lg">{{ __('Ongoing Election Horizontal Bars') }}</flux:heading>
                <div class="mt-4 space-y-8">
                    @foreach ($elections as $election)
                        @php($rows = \Illuminate\Support\Facades\DB::table('votes as v')->join('voter_verifications as vv', function($join){$join->on('vv.user_id','=','v.user_id')->on('vv.election_id','=','v.election_id');})->select('vv.state', \Illuminate\Support\Facades\DB::raw('count(*) as count'))->where('v.election_id', $election->id)->where('vv.status','approved')->groupBy('vv.state')->get())
                        @php($map = [])
                        @foreach ($rows as $r)
                            @php($map[$r->state ?? ''] = (int) $r->count)
                        @endforeach
                        @php($labels = $states)
                        @php($values = [])
                        @foreach ($states as $s)
                            @php($values[] = $map[$s] ?? 0)
                        @endforeach
                        @php($colors = ['#10b981','#3b82f6','#f59e0b','#ef4444','#8b5cf6','#06b6d4','#22c55e','#a855f7','#14b8a6','#e11d48','#7c3aed','#16a34a','#ca8a04','#1d4ed8','#dc2626','#64748b','#22d3ee','#f97316','#84cc16','#0ea5e9'])
                        <div>
                            <flux:heading size="md">{{ $election->title }}</flux:heading>
                            <div class="mt-3">
                                @php($barColors = [])
                                @foreach ($labels as $i => $lbl)
                                    @php($barColors[] = $colors[$i % count($colors)])
                                @endforeach
                                <div class="relative" style="height: {{ max(600, count($labels) * 20) }}px;">
                                    <canvas id="chart-{{ $election->id }}"></canvas>
                                </div>
                                <script>
                                    (function(){
                                        var el = document.getElementById('chart-{{ $election->id }}');
                                        if (!el) return;
                                        var ctx = el.getContext('2d');
                                        var data = {
                                            labels: @json($labels),
                                            datasets: [{
                                                label: "{{ __('Votes') }}",
                                                data: @json($values),
                                                backgroundColor: @json($barColors),
                                                borderWidth: 0,
                                                barThickness: 14
                                            }]
                                        };
                                        var labelsPlugin = {
                                            id: 'labelsPlugin',
                                            afterDatasetsDraw: function(chart){
                                                var ctx2 = chart.ctx;
                                                ctx2.save();
                                                var ds = chart.data.datasets[0];
                                                var meta = chart.getDatasetMeta(0);
                                                var bars = meta.data || [];
                                                bars.forEach(function(b, i){
                                                    var v = ds.data[i];
                                                    ctx2.fillStyle = 'rgba(31,41,55,0.9)';
                                                    ctx2.font = '11px sans-serif';
                                                    ctx2.textAlign = 'left';
                                                    ctx2.fillText(v, b.x + 6, b.y + 3);
                                                });
                                                ctx2.restore();
                                            }
                                        };
                                        var cfg = {
                                            type: 'bar',
                                            data: data,
                                            options: {
                                                indexAxis: 'y',
                                                responsive: true,
                                                maintainAspectRatio: false,
                                                scales: {
                                                    x: { display: true, beginAtZero: true, min: 0 },
                                                    y: { display: true, ticks: { autoSkip: false, font: { size: 11 } } }
                                                },
                                                plugins: { legend: { display: false } }
                                            },
                                            plugins: [labelsPlugin]
                                        };
                                        var start = function(){ new window.Chart(ctx, cfg); };
                                        var ensure = function(){
                                            if (window.Chart) start();
                                            else {
                                                var s = document.createElement('script');
                                                s.src = 'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js';
                                                s.onload = start;
                                                document.head.appendChild(s);
                                            }
                                        };
                                        if (document.readyState === 'complete') ensure();
                                        else window.addEventListener('load', ensure);
                                    })();
                                </script>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</x-layouts.app>