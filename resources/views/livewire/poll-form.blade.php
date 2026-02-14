<div class="space-y-5">
    <div class="flex items-center justify-between">
        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">
            {{ $pollId ? __('larapoll::messages.edit_poll') : __('larapoll::messages.create_poll') }}
        </h3>
        <button wire:click="cancel" type="button" class="rounded-md p-1 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-700">
            <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
        </button>
    </div>

    <form wire:submit="save" class="space-y-5">
        {{-- Title --}}
        <div>
            <label for="title" class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">{{ __('larapoll::messages.title') }} <span class="text-red-400">*</span></label>
            <input wire:model="title" type="text" id="title"
                class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-900 placeholder-gray-400 transition focus:border-gray-300 focus:bg-white focus:ring-0 dark:border-gray-600 dark:bg-gray-700/50 dark:text-white dark:focus:border-gray-500 dark:focus:bg-gray-700"
                placeholder="{{ __('larapoll::messages.enter_poll_title') }}">
            @error('title') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
        </div>

        {{-- Description --}}
        <div>
            <label for="description" class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">{{ __('larapoll::messages.description') }}</label>
            <textarea wire:model="description" id="description" rows="2"
                class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-900 placeholder-gray-400 transition focus:border-gray-300 focus:bg-white focus:ring-0 dark:border-gray-600 dark:bg-gray-700/50 dark:text-white dark:focus:border-gray-500 dark:focus:bg-gray-700"
                placeholder="{{ __('larapoll::messages.optional_description') }}"></textarea>
            @error('description') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
        </div>

        {{-- Type & Status --}}
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label for="type" class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">{{ __('larapoll::messages.type') }} <span class="text-red-400">*</span></label>
                <select wire:model="type" id="type"
                    class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-900 transition focus:border-gray-300 focus:bg-white focus:ring-0 dark:border-gray-600 dark:bg-gray-700/50 dark:text-white dark:focus:border-gray-500">
                    @foreach($types as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
                @error('type') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="status" class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">{{ __('larapoll::messages.status') }} <span class="text-red-400">*</span></label>
                <select wire:model="status" id="status"
                    class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-900 transition focus:border-gray-300 focus:bg-white focus:ring-0 dark:border-gray-600 dark:bg-gray-700/50 dark:text-white dark:focus:border-gray-500">
                    @foreach($statuses as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
                @error('status') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>
        </div>

        {{-- Dates --}}
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label for="starts_at" class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">{{ __('larapoll::messages.start_date') }}</label>
                <input wire:model="starts_at" type="datetime-local" id="starts_at"
                    class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-900 transition focus:border-gray-300 focus:bg-white focus:ring-0 dark:border-gray-600 dark:bg-gray-700/50 dark:text-white dark:focus:border-gray-500">
                @error('starts_at') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="ends_at" class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">{{ __('larapoll::messages.end_date') }}</label>
                <input wire:model="ends_at" type="datetime-local" id="ends_at"
                    class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-900 transition focus:border-gray-300 focus:bg-white focus:ring-0 dark:border-gray-600 dark:bg-gray-700/50 dark:text-white dark:focus:border-gray-500">
                @error('ends_at') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>
        </div>

        {{-- Selection Limits & Max Votes --}}
        <div class="grid grid-cols-3 gap-3">
            <div>
                <label for="min_selections" class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">{{ __('larapoll::messages.min_selections') }}</label>
                <input wire:model="min_selections" type="number" id="min_selections" min="1"
                    class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-900 transition focus:border-gray-300 focus:bg-white focus:ring-0 dark:border-gray-600 dark:bg-gray-700/50 dark:text-white dark:focus:border-gray-500">
                @error('min_selections') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="max_selections" class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">{{ __('larapoll::messages.max_selections') }}</label>
                <input wire:model="max_selections" type="number" id="max_selections" min="1"
                    class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-900 transition focus:border-gray-300 focus:bg-white focus:ring-0 dark:border-gray-600 dark:bg-gray-700/50 dark:text-white dark:focus:border-gray-500">
                @error('max_selections') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="max_votes_per_user" class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">{{ __('larapoll::messages.max_votes_per_user') }}</label>
                <input wire:model="max_votes_per_user" type="number" id="max_votes_per_user" min="1"
                    class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-900 transition focus:border-gray-300 focus:bg-white focus:ring-0 dark:border-gray-600 dark:bg-gray-700/50 dark:text-white dark:focus:border-gray-500">
                @error('max_votes_per_user') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>
        </div>

        {{-- Toggles --}}
        <div class="flex flex-wrap gap-x-5 gap-y-2.5">
            @foreach([
                'is_anonymous' => __('larapoll::messages.anonymous_voting'),
                'show_results_before_close' => __('larapoll::messages.show_results_before_close'),
                'allow_vote_change' => __('larapoll::messages.allow_vote_change'),
                'requires_comment' => __('larapoll::messages.require_comment_with_vote'),
            ] as $field => $label)
                <label class="inline-flex cursor-pointer items-center gap-2">
                    <input wire:model="{{ $field }}" type="checkbox"
                        class="size-3.5 rounded border-gray-300 text-gray-900 transition focus:ring-0 focus:ring-offset-0 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    <span class="text-xs text-gray-600 dark:text-gray-400">{{ $label }}</span>
                </label>
            @endforeach
            <label class="inline-flex cursor-pointer items-center gap-2">
                <input wire:model.live="allow_custom_options" type="checkbox"
                    class="size-3.5 rounded border-gray-300 text-gray-900 transition focus:ring-0 focus:ring-offset-0 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                <span class="text-xs text-gray-600 dark:text-gray-400">{{ __('larapoll::messages.allow_custom_options') }}</span>
            </label>
        </div>

        {{-- Max Custom Options (conditional) --}}
        @if($allow_custom_options)
            <div class="max-w-48">
                <label for="max_custom_options" class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">{{ __('larapoll::messages.max_custom_options') }}</label>
                <input wire:model="max_custom_options" type="number" id="max_custom_options" min="1"
                    class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-900 transition focus:border-gray-300 focus:bg-white focus:ring-0 dark:border-gray-600 dark:bg-gray-700/50 dark:text-white dark:focus:border-gray-500">
                @error('max_custom_options') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>
        @endif

        {{-- Options --}}
        <div>
            <div class="mb-2 flex items-center justify-between">
                <label class="text-xs font-medium text-gray-600 dark:text-gray-400">{{ __('larapoll::messages.options_label') }} <span class="text-red-400">*</span></label>
                <button wire:click.prevent="addOption" type="button"
                    class="inline-flex items-center gap-1 text-xs font-medium text-gray-500 transition hover:text-gray-900 dark:text-gray-400 dark:hover:text-white">
                    <svg class="size-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                    {{ __('larapoll::messages.add_option') }}
                </button>
            </div>
            @error('options') <p class="mb-2 text-xs text-red-500">{{ $message }}</p> @enderror

            <div class="space-y-2">
                @foreach($options as $index => $option)
                    <div wire:key="option-{{ $index }}" class="flex items-start gap-2">
                        <span class="mt-2.5 text-xs font-medium text-gray-400">{{ $index + 1 }}</span>
                        <div class="flex-1 space-y-1.5">
                            <input wire:model="options.{{ $index }}.title" type="text"
                                class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-900 placeholder-gray-400 transition focus:border-gray-300 focus:bg-white focus:ring-0 dark:border-gray-600 dark:bg-gray-700/50 dark:text-white dark:focus:border-gray-500"
                                placeholder="{{ __('larapoll::messages.option') }} {{ $index + 1 }}">
                            @error("options.{$index}.title") <p class="text-xs text-red-500">{{ $message }}</p> @enderror
                            <input wire:model="options.{{ $index }}.description" type="text"
                                class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-1.5 text-xs text-gray-700 placeholder-gray-400 transition focus:border-gray-300 focus:bg-white focus:ring-0 dark:border-gray-600 dark:bg-gray-700/50 dark:text-gray-300 dark:focus:border-gray-500"
                                placeholder="{{ __('larapoll::messages.description_optional') }}">
                        </div>
                        @if(count($options) > 2)
                            <button wire:click.prevent="removeOption({{ $index }})" type="button"
                                class="mt-2 rounded-md p-1 text-gray-300 transition hover:bg-red-50 hover:text-red-500 dark:text-gray-600 dark:hover:bg-red-900/20 dark:hover:text-red-400">
                                <svg class="size-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                            </button>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex items-center justify-end gap-2 border-t border-gray-100 pt-4 dark:border-gray-700">
            <button wire:click.prevent="cancel" type="button"
                class="rounded-lg px-3.5 py-2 text-xs font-medium text-gray-600 transition hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700">
                {{ __('larapoll::messages.cancel') }}
            </button>
            <button type="submit"
                class="rounded-lg bg-gray-900 px-3.5 py-2 text-xs font-medium text-white shadow-sm transition hover:bg-gray-800 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-100">
                {{ $pollId ? __('larapoll::messages.update_poll') : __('larapoll::messages.create_poll') }}
            </button>
        </div>
    </form>
</div>
