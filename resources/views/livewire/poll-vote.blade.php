<div class="rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
    {{-- Error Message --}}
    @if($errorMessage)
        <div class="mx-4 mt-4 flex items-center gap-2 rounded-lg bg-red-50 px-3 py-2.5 text-xs text-red-600 dark:bg-red-900/15 dark:text-red-400">
            <svg class="size-3.5 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" /></svg>
            {{ $errorMessage }}
        </div>
    @endif

    {{-- Voting Form --}}
    @if(!$hasVoted && $canVote)
        <form wire:submit="submitVote" class="p-4">
            <h4 class="mb-3 text-sm font-semibold text-gray-900 dark:text-white">{{ __('larapoll::messages.cast_your_vote') }}</h4>

            {{-- Yes/No or Single Choice --}}
            @if($poll->isYesNo() || $poll->isSingleChoice())
                <div class="space-y-1.5">
                    @foreach($poll->options as $option)
                        <label wire:key="opt-{{ $option->id }}" class="flex cursor-pointer items-center gap-3 rounded-lg border border-gray-100 px-3 py-2.5 transition hover:border-gray-200 hover:bg-gray-50/50 has-[:checked]:border-gray-900 has-[:checked]:bg-gray-50 dark:border-gray-700 dark:hover:border-gray-600 dark:hover:bg-gray-750 dark:has-[:checked]:border-gray-500 dark:has-[:checked]:bg-gray-750">
                            <input wire:model="selectedOption" type="radio" name="vote" value="{{ $option->id }}"
                                class="size-3.5 border-gray-300 text-gray-900 focus:ring-0 focus:ring-offset-0 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                            <div>
                                <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $option->title }}</span>
                                @if($option->description)
                                    <p class="text-[11px] text-gray-400">{{ $option->description }}</p>
                                @endif
                            </div>
                        </label>
                    @endforeach
                </div>

            {{-- Multiple Choice --}}
            @elseif($poll->isMultipleChoice())
                @if($poll->min_selections || $poll->max_selections)
                    <p class="mb-2 text-[11px] text-gray-400">
                        @if($poll->min_selections && $poll->max_selections)
                            {{ __('larapoll::messages.select_min_to_max', ['min' => $poll->min_selections, 'max' => $poll->max_selections]) }}
                        @elseif($poll->min_selections)
                            {{ __('larapoll::messages.select_at_least_min', ['min' => $poll->min_selections]) }}
                        @else
                            {{ __('larapoll::messages.select_up_to_max', ['max' => $poll->max_selections]) }}
                        @endif
                    </p>
                @endif
                <div class="space-y-1.5">
                    @foreach($poll->options as $option)
                        <label wire:key="opt-{{ $option->id }}" class="flex cursor-pointer items-center gap-3 rounded-lg border border-gray-100 px-3 py-2.5 transition hover:border-gray-200 hover:bg-gray-50/50 has-[:checked]:border-gray-900 has-[:checked]:bg-gray-50 dark:border-gray-700 dark:hover:border-gray-600 dark:hover:bg-gray-750 dark:has-[:checked]:border-gray-500 dark:has-[:checked]:bg-gray-750">
                            <input wire:model="selectedOptions" type="checkbox" value="{{ $option->id }}"
                                class="size-3.5 rounded border-gray-300 text-gray-900 focus:ring-0 focus:ring-offset-0 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                            <div>
                                <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $option->title }}</span>
                                @if($option->description)
                                    <p class="text-[11px] text-gray-400">{{ $option->description }}</p>
                                @endif
                            </div>
                        </label>
                    @endforeach
                </div>

            {{-- Rating --}}
            @elseif($poll->isRating())
                <div class="space-y-2">
                    @foreach($poll->options as $option)
                        <div wire:key="opt-{{ $option->id }}" class="rounded-lg border border-gray-100 px-3 py-2.5 dark:border-gray-700">
                            <div class="mb-2 text-sm font-medium text-gray-900 dark:text-white">{{ $option->title }}</div>
                            <div class="flex gap-1.5">
                                @for($i = config('larapoll.rating.min', 1); $i <= config('larapoll.rating.max', 5); $i++)
                                    <button wire:click.prevent="$set('rating', {{ $i }}); $set('selectedOption', {{ $option->id }})" type="button"
                                        class="flex size-8 items-center justify-center rounded-md text-xs font-medium transition
                                            {{ $selectedOption === $option->id && $rating === $i
                                                ? 'bg-gray-900 text-white shadow-sm dark:bg-white dark:text-gray-900'
                                                : 'border border-gray-200 text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:border-gray-600 dark:text-gray-400 dark:hover:border-gray-500' }}">
                                        {{ $i }}
                                    </button>
                                @endfor
                            </div>
                        </div>
                    @endforeach
                </div>

            {{-- Ranked --}}
            @elseif($poll->isRanked())
                <p class="mb-2 text-[11px] text-gray-400">{{ __('larapoll::messages.rank_options_hint') }}</p>
                <div class="space-y-1.5">
                    @foreach($poll->options as $option)
                        <div wire:key="opt-{{ $option->id }}" class="flex items-center gap-3 rounded-lg border border-gray-100 px-3 py-2.5 dark:border-gray-700">
                            <select wire:model="rankings.{{ $option->id }}"
                                class="w-14 rounded-md border border-gray-200 bg-gray-50 px-2 py-1 text-center text-xs font-medium text-gray-700 focus:border-gray-300 focus:ring-0 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                <option value="">-</option>
                                @for($i = 1; $i <= $poll->options->count(); $i++)
                                    <option value="{{ $i }}">{{ $i }}</option>
                                @endfor
                            </select>
                            <div>
                                <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $option->title }}</span>
                                @if($option->description)
                                    <p class="text-[11px] text-gray-400">{{ $option->description }}</p>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- Custom Option --}}
            @if($canAddCustomOption)
                <div class="mt-3 rounded-lg border border-dashed border-gray-200 p-3 dark:border-gray-600">
                    <label class="mb-1.5 flex items-center gap-1.5 text-xs font-medium text-gray-500 dark:text-gray-400">
                        {{ __('larapoll::messages.add_your_own_option') }}
                        @if($poll->max_custom_options)
                            <span class="text-[10px] text-gray-300">({{ $poll->getCustomOptionCount() }}/{{ $poll->max_custom_options }})</span>
                        @endif
                    </label>
                    <div class="flex gap-2">
                        <input wire:model="customOptionTitle" type="text"
                            class="grow rounded-md border border-gray-200 bg-gray-50 px-3 py-1.5 text-sm text-gray-900 placeholder-gray-400 focus:border-gray-300 focus:bg-white focus:ring-0 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:focus:border-gray-500"
                            placeholder="{{ __('larapoll::messages.enter_option_title') }}">
                        <button wire:click.prevent="addCustomOption" type="button"
                            class="shrink-0 rounded-md bg-gray-100 px-3 py-1.5 text-xs font-medium text-gray-600 transition hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">
                            {{ __('larapoll::messages.add') }}
                        </button>
                    </div>
                </div>
            @endif

            {{-- Comment --}}
            @if($poll->requires_comment || config('larapoll.features.vote_comments', true))
                <div class="mt-3">
                    <label class="mb-1 block text-xs font-medium text-gray-500 dark:text-gray-400">
                        {{ __('larapoll::messages.comment_label') }}{{ $poll->requires_comment ? ' *' : '' }}
                    </label>
                    <textarea wire:model="comment" rows="2"
                        class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-900 placeholder-gray-400 transition focus:border-gray-300 focus:bg-white focus:ring-0 dark:border-gray-600 dark:bg-gray-700/50 dark:text-white dark:focus:border-gray-500"
                        placeholder="{{ __('larapoll::messages.add_a_comment') }}"></textarea>
                </div>
            @endif

            <button type="submit" wire:loading.attr="disabled"
                class="mt-4 w-full rounded-lg bg-gray-900 px-4 py-2.5 text-xs font-medium text-white shadow-sm transition hover:bg-gray-800 disabled:opacity-50 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-100">
                <span wire:loading.remove>{{ __('larapoll::messages.submit_vote') }}</span>
                <span wire:loading>{{ __('larapoll::messages.submitting') }}</span>
            </button>
        </form>

    {{-- Already Voted --}}
    @elseif($hasVoted)
        <div class="p-4">
            <div class="flex items-center gap-2">
                <span class="flex size-5 items-center justify-center rounded-full bg-emerald-100 dark:bg-emerald-900/30">
                    <svg class="size-3 text-emerald-600 dark:text-emerald-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                </span>
                <span class="text-sm font-medium text-gray-900 dark:text-white">{{ __('larapoll::messages.you_have_voted') }}</span>
            </div>

            {{-- Inline Results --}}
            @if($canShowResults && $showResults)
                <div class="mt-4">
                    <livewire:larapoll-poll-results :poll="$poll" :key="'inline-results-' . $poll->id" />
                </div>
            @elseif($canShowResults)
                <button wire:click="toggleResults" class="mt-2 text-xs font-medium text-gray-500 transition hover:text-gray-900 dark:text-gray-400 dark:hover:text-white">
                    {{ __('larapoll::messages.show_results') }}
                </button>
            @endif

            {{-- Action Buttons --}}
            @if($canChange || $canRetract)
                <div class="mt-3 flex gap-2 border-t border-gray-100 pt-3 dark:border-gray-700">
                    @if($canChange)
                        <button wire:click="$set('showResults', false)" type="button"
                            class="rounded-lg px-3 py-1.5 text-xs font-medium text-gray-600 transition hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700">
                            {{ __('larapoll::messages.change_vote') }}
                        </button>
                    @endif
                    @if($canRetract)
                        <button wire:click="retractVote" wire:confirm="{{ __('larapoll::messages.retract_your_vote') }}" type="button"
                            class="rounded-lg px-3 py-1.5 text-xs font-medium text-red-500 transition hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/20">
                            {{ __('larapoll::messages.retract_vote') }}
                        </button>
                    @endif
                </div>
            @endif
        </div>

    {{-- Not Authorized / Closed --}}
    @else
        <div class="p-4">
            <div class="rounded-lg bg-gray-50 px-4 py-6 text-center dark:bg-gray-900/30">
                <p class="text-xs text-gray-400">
                    @if($poll->isClosed() || $poll->isCancelled())
                        {{ __('larapoll::messages.poll_is_closed') }}
                    @elseif(!auth()->check())
                        {{ __('larapoll::messages.please_log_in') }}
                    @else
                        {{ __('larapoll::messages.not_eligible') }}
                    @endif
                </p>
            </div>

            {{-- Results for closed polls --}}
            @if($canShowResults)
                <div class="mt-4">
                    <livewire:larapoll-poll-results :poll="$poll" :key="'closed-results-' . $poll->id" />
                </div>
            @endif
        </div>
    @endif
</div>
