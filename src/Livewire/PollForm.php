<?php

namespace Aftandilmmd\PollVote\Livewire;

use Aftandilmmd\PollVote\Enums\PollStatus;
use Aftandilmmd\PollVote\Enums\PollType;
use Aftandilmmd\PollVote\Models\Poll;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class PollForm extends Component
{
    public ?int $pollId = null;

    public ?string $pollableType = null;

    public ?int $pollableId = null;

    public string $title = '';

    public string $description = '';

    public string $type = 'single_choice';

    public string $status = 'draft';

    public bool $is_anonymous = false;

    public bool $show_results_before_close = false;

    public bool $allow_vote_change = false;

    public bool $requires_comment = false;

    public bool $allow_custom_options = false;

    public ?int $max_custom_options = null;

    public ?int $max_votes_per_user = null;

    public ?int $min_selections = null;

    public ?int $max_selections = null;

    public ?string $starts_at = null;

    public ?string $ends_at = null;

    /** @var array<int, array{title: string, description: string}> */
    public array $options = [];

    public function mount(?int $pollId = null, ?string $pollableType = null, ?int $pollableId = null): void
    {
        $this->pollableType = $pollableType;
        $this->pollableId = $pollableId;

        if ($pollId) {
            $this->pollId = $pollId;
            $poll = Poll::with('options')->findOrFail($pollId);
            $this->fill([
                'title' => $poll->title,
                'description' => $poll->description ?? '',
                'type' => $poll->type->value,
                'status' => $poll->status->value,
                'is_anonymous' => $poll->is_anonymous,
                'show_results_before_close' => $poll->show_results_before_close,
                'allow_vote_change' => $poll->allow_vote_change,
                'requires_comment' => $poll->requires_comment,
                'allow_custom_options' => $poll->allow_custom_options,
                'max_custom_options' => $poll->max_custom_options,
                'max_votes_per_user' => $poll->max_votes_per_user,
                'min_selections' => $poll->min_selections,
                'max_selections' => $poll->max_selections,
                'starts_at' => $poll->starts_at?->format('Y-m-d\TH:i'),
                'ends_at' => $poll->ends_at?->format('Y-m-d\TH:i'),
                'pollableType' => $poll->pollable_type,
                'pollableId' => $poll->pollable_id,
            ]);

            $this->options = $poll->options->map(fn ($o) => [
                'title' => $o->title,
                'description' => $o->description ?? '',
            ])->toArray();
        }

        if (empty($this->options)) {
            $this->options = [
                ['title' => '', 'description' => ''],
                ['title' => '', 'description' => ''],
            ];
        }
    }

    public function render(): View
    {
        return view('poll-vote::livewire.poll-form', [
            'types' => PollType::options(),
            'statuses' => PollStatus::options(),
        ]);
    }

    public function addOption(): void
    {
        $this->options[] = ['title' => '', 'description' => ''];
    }

    public function removeOption(int $index): void
    {
        if (count($this->options) > 2) {
            unset($this->options[$index]);
            $this->options = array_values($this->options);
        }
    }

    public function save(): void
    {
        $this->validate();

        $service = app('poll-vote');
        $attributes = [
            'title' => $this->title,
            'description' => $this->description ?: null,
            'type' => $this->type,
            'status' => $this->status,
            'is_anonymous' => $this->is_anonymous,
            'show_results_before_close' => $this->show_results_before_close,
            'allow_vote_change' => $this->allow_vote_change,
            'requires_comment' => $this->requires_comment,
            'allow_custom_options' => $this->allow_custom_options,
            'max_custom_options' => $this->allow_custom_options ? $this->max_custom_options : null,
            'max_votes_per_user' => $this->max_votes_per_user,
            'min_selections' => $this->min_selections,
            'max_selections' => $this->max_selections,
            'starts_at' => $this->starts_at ?: null,
            'ends_at' => $this->ends_at ?: null,
            'pollable_type' => $this->pollableType,
            'pollable_id' => $this->pollableId,
        ];

        if ($this->pollId) {
            $poll = Poll::findOrFail($this->pollId);
            $this->authorizePollManagement($poll);
            $service->update($poll, $attributes);

            // Sync options
            $poll->options()->delete();
            foreach ($this->options as $index => $option) {
                if (! empty($option['title'])) {
                    $service->addOption($poll, [
                        'title' => $option['title'],
                        'description' => $option['description'] ?: null,
                        'sort_order' => $index,
                    ]);
                }
            }
        } else {
            $poll = $service->create($attributes, auth()->user());

            foreach ($this->options as $index => $option) {
                if (! empty($option['title'])) {
                    $service->addOption($poll, [
                        'title' => $option['title'],
                        'description' => $option['description'] ?: null,
                        'sort_order' => $index,
                    ]);
                }
            }
        }

        $this->dispatch('poll-saved');
    }

    public function cancel(): void
    {
        $this->dispatch('poll-form-closed');
    }

    protected function authorizePollManagement(Poll $poll): void
    {
        $user = auth()->user();

        if (method_exists($user, 'canManagePoll')) {
            abort_unless($user->canManagePoll($poll), 403);
        } else {
            abort_unless($poll->created_by === $user?->getAuthIdentifier(), 403);
        }
    }

    protected function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'type' => 'required|in:'.implode(',', PollType::values()),
            'status' => 'required|in:'.implode(',', PollStatus::values()),
            'is_anonymous' => 'boolean',
            'show_results_before_close' => 'boolean',
            'allow_vote_change' => 'boolean',
            'requires_comment' => 'boolean',
            'allow_custom_options' => 'boolean',
            'max_custom_options' => 'nullable|integer|min:1',
            'max_votes_per_user' => 'nullable|integer|min:1',
            'min_selections' => 'nullable|integer|min:1',
            'max_selections' => 'nullable|integer|min:1|gte:min_selections',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after:starts_at',
            'options' => 'required|array|min:2',
            'options.*.title' => 'required|string|max:255',
            'options.*.description' => 'nullable|string|max:1000',
        ];
    }

    protected function messages(): array
    {
        return [
            'title.required' => __('poll-vote::messages.poll_title_required'),
            'options.min' => __('poll-vote::messages.options_min'),
            'options.*.title.required' => __('poll-vote::messages.option_title_required'),
            'ends_at.after' => __('poll-vote::messages.ends_at_after'),
            'max_selections.gte' => __('poll-vote::messages.max_selections_gte'),
        ];
    }
}
