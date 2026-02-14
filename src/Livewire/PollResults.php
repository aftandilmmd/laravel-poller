<?php

namespace Aftandilmmd\Larapoll\Livewire;

use Aftandilmmd\Larapoll\Models\Poll;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class PollResults extends Component
{
    public Poll $poll;

    protected $listeners = [
        'vote-cast' => '$refresh',
        'vote-changed' => '$refresh',
        'vote-retracted' => '$refresh',
    ];

    public function mount(Poll $poll): void
    {
        $this->poll = $poll->load('options');
    }

    public function render(): View
    {
        return view('larapoll::livewire.poll-results', [
            'results' => $this->poll->getResultsAsPercentages(),
            'totalVotes' => $this->poll->getTotalVotes(),
            'uniqueVoters' => $this->poll->getUniqueVoterCount(),
            'leadingOption' => $this->poll->getLeadingOption(),
        ]);
    }
}
