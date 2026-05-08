<?php

namespace Aftandilmmd\Poller\Livewire;

use Aftandilmmd\Poller\Exceptions\PollException;
use Aftandilmmd\Poller\Models\Poll;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class PollVote extends Component
{
    public Poll $poll;

    /** @var array<int> */
    public array $selectedOptions = [];

    public ?int $selectedOption = null;

    public ?int $rating = null;

    /** @var array<int, int> */
    public array $rankings = [];

    public string $comment = '';

    public string $customOptionTitle = '';

    public ?string $errorMessage = null;

    public bool $showResults = false;

    public function mount(Poll $poll): void
    {
        $this->poll = $poll->load('options');

        $user = auth()->user();
        if ($user && $this->poll->hasUserVoted($user)) {
            $this->loadExistingVotes();
            $this->showResults = $this->poll->canShowResults($user);
        }
    }

    public function render(): View
    {
        $user = auth()->user();
        $hasVoted = $user ? $this->poll->hasUserVoted($user) : false;

        $canVote = false;
        if ($user) {
            $canVote = method_exists($user, 'canVote') ? $user->canVote($this->poll) : $this->poll->isVotingOpen();
        }

        $canAddCustomOption = false;
        if ($user && $this->poll->allowsCustomOptions() && ! $this->poll->hasReachedCustomOptionLimit()) {
            $canAddCustomOption = method_exists($user, 'canAddCustomOption') ? $user->canAddCustomOption($this->poll) : true;
        }

        return view('poller::livewire.poll-vote', [
            'hasVoted' => $hasVoted,
            'canVote' => $canVote,
            'canAddCustomOption' => $canAddCustomOption,
            'canChange' => $hasVoted && $this->poll->allow_vote_change && config('poller.features.vote_changing', true),
            'canRetract' => $hasVoted && config('poller.features.vote_retraction', true),
            'canShowResults' => $this->poll->canShowResults($user),
            'results' => $this->poll->canShowResults($user) ? $this->poll->getResultsAsPercentages() : [],
            'totalVotes' => $this->poll->getTotalVotes(),
        ]);
    }

    public function submitVote(): void
    {
        $this->errorMessage = null;

        try {
            $service = app('poller');
            $user = auth()->user();

            $options = $this->getSelectedOptionIds();
            $extra = $this->buildExtra();

            if ($this->poll->hasUserVoted($user)) {
                $service->changeVote($this->poll, $user, $options, $extra);
                $this->dispatch('vote-changed');
            } else {
                $service->castVote($this->poll, $user, $options, $extra);
                $this->dispatch('vote-cast');
            }

            $this->poll->refresh();
            $this->poll->load('options');
            $this->showResults = $this->poll->canShowResults($user);
        } catch (PollException $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function addCustomOption(): void
    {
        $this->errorMessage = null;

        $title = trim($this->customOptionTitle);

        if ($title === '') {
            $this->errorMessage = __('poller::messages.please_enter_option_title');

            return;
        }

        try {
            $option = app('poller')->addCustomOption($this->poll, auth()->user(), ['title' => $title]);
            $this->customOptionTitle = '';
            $this->poll->refresh();
            $this->poll->load('options');
            $this->dispatch('custom-option-added', optionId: $option->id);
        } catch (PollException $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function retractVote(): void
    {
        $this->errorMessage = null;

        try {
            app('poller')->retractVote($this->poll, auth()->user());
            $this->reset(['selectedOptions', 'selectedOption', 'rating', 'rankings', 'comment', 'customOptionTitle', 'showResults']);
            $this->poll->refresh();
            $this->poll->load('options');
            $this->dispatch('vote-retracted');
        } catch (PollException $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function toggleResults(): void
    {
        $this->showResults = ! $this->showResults;
    }

    protected function getSelectedOptionIds(): array
    {
        if ($this->poll->isSingleChoice() || $this->poll->isYesNo()) {
            return $this->selectedOption ? [$this->selectedOption] : [];
        }

        if ($this->poll->isRating()) {
            return $this->selectedOption ? [$this->selectedOption] : [];
        }

        if ($this->poll->isRanked()) {
            return array_keys($this->rankings);
        }

        return $this->selectedOptions;
    }

    protected function buildExtra(): array
    {
        $extra = [];

        if ($this->comment && ($this->poll->requires_comment || config('poller.features.vote_comments', true))) {
            $extra['comment'] = $this->comment;
        }

        if ($this->poll->isRating() && $this->rating) {
            $extra['rating'] = $this->rating;
        }

        if ($this->poll->isRanked() && ! empty($this->rankings)) {
            $extra['ranks'] = array_values($this->rankings);
        }

        return $extra;
    }

    protected function loadExistingVotes(): void
    {
        $user = auth()->user();
        $votes = $this->poll->getUserVotes($user);

        if ($this->poll->isSingleChoice() || $this->poll->isYesNo() || $this->poll->isRating()) {
            $this->selectedOption = $votes->first()?->poll_option_id;
            $this->rating = $votes->first()?->rating;
        } elseif ($this->poll->isRanked()) {
            $this->rankings = $votes->pluck('rank', 'poll_option_id')->toArray();
        } else {
            $this->selectedOptions = $votes->pluck('poll_option_id')->toArray();
        }

        $this->comment = $votes->first()?->comment ?? '';
    }
}
