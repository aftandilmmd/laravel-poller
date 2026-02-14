<div class="space-y-3">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('larapoll::messages.results') }}</h3>
        <div class="flex items-center gap-3 text-xs text-gray-400">
            <span>{{ $totalVotes }} {{ __('larapoll::messages.total_votes_label') }}</span>
            <span class="text-gray-200 dark:text-gray-600">&middot;</span>
            <span>{{ $uniqueVoters }} {{ __('larapoll::messages.voters_label') }}</span>
        </div>
    </div>

    @if($totalVotes > 0)
        {{-- Result Bars --}}
        <div class="space-y-2">
            @foreach($results as $index => $result)
                <div class="group rounded-lg border border-gray-100 bg-white p-3 transition dark:border-gray-700 dark:bg-gray-800">
                    <div class="mb-1.5 flex items-center justify-between gap-3">
                        <div class="flex items-center gap-2">
                            @if($leadingOption && $result['option_id'] === $leadingOption->id && $totalVotes > 0)
                                <span class="flex size-4 items-center justify-center rounded-full bg-emerald-100 dark:bg-emerald-900/30">
                                    <svg class="size-2.5 text-emerald-600 dark:text-emerald-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                </span>
                            @endif
                            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $result['title'] }}</span>
                        </div>
                        <div class="flex items-baseline gap-1.5">
                            <span class="text-sm font-bold tabular-nums text-gray-900 dark:text-white">{{ $result['percentage'] }}%</span>
                            <span class="text-[10px] tabular-nums text-gray-400">({{ $result['votes_count'] }})</span>
                        </div>
                    </div>
                    <div class="h-1.5 w-full overflow-hidden rounded-full bg-gray-100 dark:bg-gray-700">
                        <div class="h-full rounded-full transition-all duration-700 ease-out
                            {{ $leadingOption && $result['option_id'] === $leadingOption->id ? 'bg-emerald-500 dark:bg-emerald-400' : 'bg-gray-400 dark:bg-gray-500' }}"
                            style="width: {{ $result['percentage'] }}%"></div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Leading Option --}}
        @if($leadingOption)
            <div class="flex items-center gap-2 rounded-lg bg-emerald-50 px-3 py-2 dark:bg-emerald-900/10">
                <svg class="size-3.5 text-emerald-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 18.75h-9m9 0a3 3 0 0 1 3 3h-15a3 3 0 0 1 3-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.007 0H9.497m5.007 0a7.454 7.454 0 0 1-.982-3.172M9.497 14.25a7.454 7.454 0 0 0 .981-3.172M5.25 4.236c-.982.143-1.954.317-2.916.52A6.003 6.003 0 0 0 7.73 9.728M5.25 4.236V4.5c0 2.108.966 3.99 2.48 5.228M5.25 4.236V2.721C7.456 2.41 9.71 2.25 12 2.25c2.291 0 4.545.16 6.75.47v1.516M18.75 4.236c.982.143 1.954.317 2.916.52A6.003 6.003 0 0 1 16.27 9.728M18.75 4.236V4.5c0 2.108-.966 3.99-2.48 5.228m0 0a6.023 6.023 0 0 1-2.27.308 6.023 6.023 0 0 1-2.27-.308" /></svg>
                <p class="text-xs text-emerald-700 dark:text-emerald-400">
                    <span class="font-semibold">{{ __('larapoll::messages.leading') }}:</span> {{ $leadingOption->title }}
                    <span class="text-emerald-500">({{ $leadingOption->votes_count }} {{ __('larapoll::messages.votes') }})</span>
                </p>
            </div>
        @endif
    @else
        <div class="rounded-xl border border-dashed border-gray-200 px-4 py-12 text-center dark:border-gray-700">
            <p class="text-xs text-gray-400">{{ __('larapoll::messages.no_votes_yet') }}</p>
        </div>
    @endif
</div>
