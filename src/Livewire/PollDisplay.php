<?php

namespace Aftandilmmd\PollVote\Livewire;

use Aftandilmmd\PollVote\Models\Poll;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class PollDisplay extends Component
{
    public Poll $poll;

    public string $activeTab = 'overview';

    protected $listeners = [
        'vote-cast' => '$refresh',
        'vote-changed' => '$refresh',
        'vote-retracted' => '$refresh',
    ];

    public function mount(Poll $poll): void
    {
        $this->poll = $poll->load(['options', 'creator']);
    }

    public function render(): View
    {
        $user = auth()->user();
        $hasVoted = $user ? $this->poll->hasUserVoted($user) : false;
        $userVotes = $user ? $this->poll->getUserVotes($user) : collect();
        $canShowResults = $this->poll->canShowResults($user);

        return view('poll-vote::livewire.poll-display', [
            'hasVoted' => $hasVoted,
            'userVotes' => $userVotes,
            'canShowResults' => $canShowResults,
            'results' => $canShowResults ? $this->poll->getResultsAsPercentages() : [],
            'totalVotes' => $this->poll->getTotalVotes(),
            'uniqueVoters' => $this->poll->getUniqueVoterCount(),
        ]);
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
    }
}
