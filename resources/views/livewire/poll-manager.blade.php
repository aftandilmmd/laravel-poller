<div class="space-y-4">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('poller::messages.polls') }}</h2>
        <button wire:click="createPoll" type="button"
            class="inline-flex items-center gap-1.5 rounded-lg bg-gray-900 px-3.5 py-2 text-xs font-medium text-white shadow-sm transition hover:bg-gray-800 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-100">
            <svg class="size-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
            {{ __('poller::messages.create_poll') }}
        </button>
    </div>

    {{-- Filters --}}
    <div class="flex flex-col gap-2 sm:flex-row">
        <div class="relative flex-1">
            <svg class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" /></svg>
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="{{ __('poller::messages.search_polls') }}"
                class="w-full rounded-lg border border-gray-200 bg-gray-50 py-2 pl-9 pr-3 text-sm text-gray-900 placeholder-gray-400 transition focus:border-gray-300 focus:bg-white focus:ring-0 dark:border-gray-700 dark:bg-gray-800/50 dark:text-white dark:placeholder-gray-500 dark:focus:border-gray-600 dark:focus:bg-gray-800">
        </div>
        <select wire:model.live="statusFilter"
            class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-700 transition focus:border-gray-300 focus:bg-white focus:ring-0 dark:border-gray-700 dark:bg-gray-800/50 dark:text-gray-300 dark:focus:border-gray-600">
            <option value="">{{ __('poller::messages.all_statuses') }}</option>
            @foreach($statuses as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </select>
        <select wire:model.live="typeFilter"
            class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-700 transition focus:border-gray-300 focus:bg-white focus:ring-0 dark:border-gray-700 dark:bg-gray-800/50 dark:text-gray-300 dark:focus:border-gray-600">
            <option value="">{{ __('poller::messages.all_types') }}</option>
            @foreach($types as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </select>
    </div>

    {{-- Poll Form --}}
    @if($showForm)
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <livewire:poller-poll-form
                :poll-id="$editingPollId"
                :pollable-type="$pollableType"
                :pollable-id="$pollableId"
                :key="'form-' . ($editingPollId ?? 'new')"
            />
        </div>
    @endif

    {{-- Poll List --}}
    <div class="divide-y divide-gray-100 overflow-hidden rounded-xl border border-gray-200 bg-white dark:divide-gray-700/50 dark:border-gray-700 dark:bg-gray-800">
        @forelse($polls as $poll)
            <div wire:key="poll-{{ $poll->id }}" class="group relative flex items-center gap-4 px-4 py-3 transition hover:bg-gray-50/80 dark:hover:bg-gray-750">
                {{-- Status indicator --}}
                <div class="flex size-9 shrink-0 items-center justify-center rounded-lg
                    {{ $poll->status->color() === 'green' ? 'bg-emerald-50 text-emerald-600 dark:bg-emerald-900/20 dark:text-emerald-400' : '' }}
                    {{ $poll->status->color() === 'gray' ? 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400' : '' }}
                    {{ $poll->status->color() === 'blue' ? 'bg-blue-50 text-blue-600 dark:bg-blue-900/20 dark:text-blue-400' : '' }}
                    {{ $poll->status->color() === 'red' ? 'bg-red-50 text-red-500 dark:bg-red-900/20 dark:text-red-400' : '' }}">
                    @if($poll->isYesNo())
                        <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                    @elseif($poll->isSingleChoice())
                        <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0ZM3.75 12h.007v.008H3.75V12Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm-.375 5.25h.007v.008H3.75v-.008Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" /></svg>
                    @elseif($poll->isMultipleChoice())
                        <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                    @elseif($poll->isRating())
                        <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.562.562 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z" /></svg>
                    @else
                        <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 7.5 7.5 3m0 0L12 7.5M7.5 3v13.5m13.5-6L16.5 16.5m0 0L12 10.5m4.5 6V3" /></svg>
                    @endif
                </div>

                {{-- Content --}}
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2">
                        <h3 class="truncate text-sm font-medium text-gray-900 dark:text-white">{{ $poll->title }}</h3>
                        <span class="shrink-0 rounded-full px-2 py-0.5 text-[10px] font-medium leading-tight
                            {{ $poll->status->color() === 'green' ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' : '' }}
                            {{ $poll->status->color() === 'gray' ? 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400' : '' }}
                            {{ $poll->status->color() === 'blue' ? 'bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400' : '' }}
                            {{ $poll->status->color() === 'red' ? 'bg-red-50 text-red-600 dark:bg-red-900/30 dark:text-red-400' : '' }}">
                            {{ $poll->status->label() }}
                        </span>
                    </div>
                    <div class="mt-0.5 flex items-center gap-3 text-xs text-gray-500 dark:text-gray-400">
                        <span>{{ $poll->type->label() }}</span>
                        <span class="text-gray-300 dark:text-gray-600">&middot;</span>
                        <span>{{ $poll->votes_count ?? 0 }} {{ __('poller::messages.votes') }}</span>
                        <span class="text-gray-300 dark:text-gray-600">&middot;</span>
                        <span>{{ $poll->options_count ?? $poll->options->count() }} {{ __('poller::messages.options') }}</span>
                        @if($poll->ends_at)
                            <span class="text-gray-300 dark:text-gray-600">&middot;</span>
                            <span>{{ $poll->ends_at->diffForHumans(short: true) }}</span>
                        @endif
                    </div>
                </div>

                {{-- Actions --}}
                <div class="flex shrink-0 items-center gap-0.5 opacity-0 transition-opacity group-hover:opacity-100">
                    @if($poll->isDraft())
                        <button wire:click="activatePoll({{ $poll->id }})" wire:confirm="{{ __('poller::messages.activate_this_poll') }}" title="{{ __('poller::messages.activate') }}"
                            class="rounded-md p-1.5 text-emerald-600 transition hover:bg-emerald-50 dark:text-emerald-400 dark:hover:bg-emerald-900/20">
                            <svg class="size-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.347a1.125 1.125 0 0 1 0 1.972l-11.54 6.347a1.125 1.125 0 0 1-1.667-.986V5.653Z" /></svg>
                        </button>
                    @endif
                    @if($poll->isActive())
                        <button wire:click="closePoll({{ $poll->id }})" wire:confirm="{{ __('poller::messages.close_this_poll') }}" title="{{ __('poller::messages.close') }}"
                            class="rounded-md p-1.5 text-blue-600 transition hover:bg-blue-50 dark:text-blue-400 dark:hover:bg-blue-900/20">
                            <svg class="size-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" /></svg>
                        </button>
                    @endif
                    @if($poll->isDraft() || $poll->isActive())
                        <button wire:click="cancelPoll({{ $poll->id }})" wire:confirm="{{ __('poller::messages.cancel_this_poll') }}" title="{{ __('poller::messages.cancel') }}"
                            class="rounded-md p-1.5 text-orange-500 transition hover:bg-orange-50 dark:text-orange-400 dark:hover:bg-orange-900/20">
                            <svg class="size-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 0 0 5.636 5.636m12.728 12.728A9 9 0 0 1 5.636 5.636m12.728 12.728L5.636 5.636" /></svg>
                        </button>
                    @endif
                    <button wire:click="editPoll({{ $poll->id }})" title="{{ __('poller::messages.edit') }}"
                        class="rounded-md p-1.5 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-700 dark:hover:text-gray-300">
                        <svg class="size-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" /></svg>
                    </button>
                    <button wire:click="duplicatePoll({{ $poll->id }})" title="{{ __('poller::messages.duplicate') }}"
                        class="rounded-md p-1.5 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-700 dark:hover:text-gray-300">
                        <svg class="size-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 0 1-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 0 1 1.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 0 0-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 0 1-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 0 0-3.375-3.375h-1.5a1.125 1.125 0 0 1-1.125-1.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H9.75" /></svg>
                    </button>
                    <button wire:click="deletePoll({{ $poll->id }})" wire:confirm="{{ __('poller::messages.delete_this_poll') }}" title="{{ __('poller::messages.delete') }}"
                        class="rounded-md p-1.5 text-gray-400 transition hover:bg-red-50 hover:text-red-500 dark:hover:bg-red-900/20 dark:hover:text-red-400">
                        <svg class="size-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>
                    </button>
                </div>
            </div>
        @empty
            <div class="px-4 py-16 text-center">
                <div class="mx-auto mb-3 flex size-10 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-700">
                    <svg class="size-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 0 1 0 3.75H5.625a1.875 1.875 0 0 1 0-3.75Z" /></svg>
                </div>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('poller::messages.no_polls_found') }}</p>
                <button wire:click="createPoll" type="button"
                    class="mt-3 inline-flex items-center gap-1.5 rounded-lg bg-gray-900 px-3.5 py-2 text-xs font-medium text-white transition hover:bg-gray-800 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-100">
                    {{ __('poller::messages.create_your_first_poll') }}
                </button>
            </div>
        @endforelse
    </div>

    {{-- Pagination --}}
    @if($polls->hasPages())
        <div>{{ $polls->links() }}</div>
    @endif
</div>
