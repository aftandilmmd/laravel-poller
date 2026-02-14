<div class="space-y-5">
    {{-- Poll Header --}}
    <div class="rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
        <div class="p-5">
            <div class="flex items-start justify-between gap-4">
                <div class="min-w-0">
                    <div class="flex items-center gap-2.5">
                        <h2 class="truncate text-lg font-semibold text-gray-900 dark:text-white">{{ $poll->title }}</h2>
                        <span class="shrink-0 rounded-full px-2 py-0.5 text-[10px] font-medium leading-tight
                            {{ $poll->status->color() === 'green' ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' : '' }}
                            {{ $poll->status->color() === 'gray' ? 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400' : '' }}
                            {{ $poll->status->color() === 'blue' ? 'bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400' : '' }}
                            {{ $poll->status->color() === 'red' ? 'bg-red-50 text-red-600 dark:bg-red-900/30 dark:text-red-400' : '' }}">
                            {{ $poll->status->label() }}
                        </span>
                    </div>
                    @if($poll->description)
                        <p class="mt-1.5 text-sm leading-relaxed text-gray-500 dark:text-gray-400">{{ $poll->description }}</p>
                    @endif
                </div>
                <span class="shrink-0 rounded-lg bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                    {{ $poll->type->label() }}
                </span>
            </div>

            {{-- Compact Stats --}}
            <div class="mt-4 grid grid-cols-4 gap-px overflow-hidden rounded-lg border border-gray-100 bg-gray-100 dark:border-gray-700 dark:bg-gray-700">
                <div class="bg-white px-3 py-2.5 text-center dark:bg-gray-800">
                    <p class="text-lg font-bold text-gray-900 dark:text-white">{{ $totalVotes }}</p>
                    <p class="text-[10px] font-medium uppercase tracking-wider text-gray-400">{{ __('larapoll::messages.total_votes') }}</p>
                </div>
                <div class="bg-white px-3 py-2.5 text-center dark:bg-gray-800">
                    <p class="text-lg font-bold text-gray-900 dark:text-white">{{ $uniqueVoters }}</p>
                    <p class="text-[10px] font-medium uppercase tracking-wider text-gray-400">{{ __('larapoll::messages.voters') }}</p>
                </div>
                <div class="bg-white px-3 py-2.5 text-center dark:bg-gray-800">
                    <p class="text-lg font-bold text-gray-900 dark:text-white">{{ $poll->options->count() }}</p>
                    <p class="text-[10px] font-medium uppercase tracking-wider text-gray-400">{{ __('larapoll::messages.options_count') }}</p>
                </div>
                <div class="bg-white px-3 py-2.5 text-center dark:bg-gray-800">
                    @if($poll->ends_at && $poll->isActive())
                        <p class="text-lg font-bold text-gray-900 dark:text-white">{{ $poll->ends_at->diffForHumans(short: true) }}</p>
                    @else
                        <p class="text-lg font-bold text-gray-400">-</p>
                    @endif
                    <p class="text-[10px] font-medium uppercase tracking-wider text-gray-400">{{ $poll->ends_at ? __('larapoll::messages.ends') : __('larapoll::messages.no_deadline') }}</p>
                </div>
            </div>
        </div>

        {{-- Settings Tags --}}
        @if($poll->is_anonymous || $poll->allow_vote_change || $poll->requires_comment || $poll->show_results_before_close)
            <div class="flex flex-wrap gap-1.5 border-t border-gray-100 px-5 py-3 dark:border-gray-700">
                @if($poll->is_anonymous)
                    <span class="rounded-md bg-purple-50 px-2 py-0.5 text-[10px] font-medium text-purple-600 dark:bg-purple-900/20 dark:text-purple-400">{{ __('larapoll::messages.anonymous') }}</span>
                @endif
                @if($poll->allow_vote_change)
                    <span class="rounded-md bg-amber-50 px-2 py-0.5 text-[10px] font-medium text-amber-600 dark:bg-amber-900/20 dark:text-amber-400">{{ __('larapoll::messages.vote_change_allowed') }}</span>
                @endif
                @if($poll->requires_comment)
                    <span class="rounded-md bg-sky-50 px-2 py-0.5 text-[10px] font-medium text-sky-600 dark:bg-sky-900/20 dark:text-sky-400">{{ __('larapoll::messages.comment_required_badge') }}</span>
                @endif
                @if($poll->show_results_before_close)
                    <span class="rounded-md bg-teal-50 px-2 py-0.5 text-[10px] font-medium text-teal-600 dark:bg-teal-900/20 dark:text-teal-400">{{ __('larapoll::messages.live_results') }}</span>
                @endif
            </div>
        @endif
    </div>

    {{-- Tabs --}}
    <div class="flex gap-1 rounded-lg bg-gray-100 p-1 dark:bg-gray-800">
        @foreach(['overview' => __('larapoll::messages.overview'), 'results' => __('larapoll::messages.results'), 'votes' => __('larapoll::messages.votes_tab')] as $tab => $label)
            <button wire:click="setTab('{{ $tab }}')"
                class="flex-1 rounded-md px-3 py-1.5 text-xs font-medium transition
                    {{ $activeTab === $tab
                        ? 'bg-white text-gray-900 shadow-sm dark:bg-gray-700 dark:text-white'
                        : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200' }}">
                {{ $label }}
            </button>
        @endforeach
    </div>

    {{-- Tab Content --}}
    @if($activeTab === 'overview')
        <livewire:larapoll-poll-vote :poll="$poll" :key="'vote-' . $poll->id" />
    @elseif($activeTab === 'results')
        @if($canShowResults)
            <livewire:larapoll-poll-results :poll="$poll" :key="'results-' . $poll->id" />
        @else
            <div class="rounded-xl border border-dashed border-gray-200 px-4 py-12 text-center dark:border-gray-700">
                <p class="text-xs text-gray-400">{{ __('larapoll::messages.results_available_after_close') }}</p>
            </div>
        @endif
    @elseif($activeTab === 'votes')
        @if(!$poll->is_anonymous)
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-gray-100 dark:border-gray-700">
                            <th class="px-4 py-2.5 text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('larapoll::messages.voter') }}</th>
                            <th class="px-4 py-2.5 text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('larapoll::messages.option_column') }}</th>
                            <th class="px-4 py-2.5 text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('larapoll::messages.comment') }}</th>
                            <th class="px-4 py-2.5 text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('larapoll::messages.date') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50 dark:divide-gray-700/50">
                        @foreach($poll->votes()->with(['user', 'option'])->latest()->get() as $vote)
                            <tr class="transition hover:bg-gray-50/50 dark:hover:bg-gray-750">
                                <td class="px-4 py-2.5 text-sm text-gray-900 dark:text-white">{{ $vote->user?->name ?? __('larapoll::messages.unknown') }}</td>
                                <td class="px-4 py-2.5 text-sm text-gray-500 dark:text-gray-400">{{ $vote->option?->title }}</td>
                                <td class="px-4 py-2.5 text-sm text-gray-400">{{ $vote->comment ?? '-' }}</td>
                                <td class="px-4 py-2.5 text-xs text-gray-400">{{ $vote->created_at->format('M d, Y H:i') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="rounded-xl border border-dashed border-gray-200 px-4 py-12 text-center dark:border-gray-700">
                <p class="text-xs text-gray-400">{{ __('larapoll::messages.anonymous_votes_hidden') }}</p>
            </div>
        @endif
    @endif
</div>
