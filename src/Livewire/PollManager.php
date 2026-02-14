<?php

namespace Aftandilmmd\Larapoll\Livewire;

use Aftandilmmd\Larapoll\Enums\PollStatus;
use Aftandilmmd\Larapoll\Enums\PollType;
use Aftandilmmd\Larapoll\Models\Poll;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class PollManager extends Component
{
    use WithPagination;

    public ?string $pollableType = null;

    public ?int $pollableId = null;

    #[Url]
    public string $search = '';

    #[Url]
    public string $statusFilter = '';

    #[Url]
    public string $typeFilter = '';

    public bool $showForm = false;

    public ?int $editingPollId = null;

    protected $listeners = [
        'poll-saved' => 'onPollSaved',
        'poll-form-closed' => 'closeForm',
    ];

    public function mount($pollable = null): void
    {
        if ($pollable instanceof Model) {
            $this->pollableType = $pollable->getMorphClass();
            $this->pollableId = $pollable->getKey();
        }
    }

    public function render(): View
    {
        $query = Poll::query()
            ->with(['creator', 'options'])
            ->withCount('votes')
            ->latest();

        if ($this->pollableType && $this->pollableId) {
            $query->where('pollable_type', $this->pollableType)
                ->where('pollable_id', $this->pollableId);
        }

        if ($this->search) {
            $query->where('title', 'like', "%{$this->search}%");
        }

        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }

        if ($this->typeFilter) {
            $query->where('type', $this->typeFilter);
        }

        return view('larapoll::livewire.poll-manager', [
            'polls' => $query->paginate(config('larapoll.pagination.polls', 20)),
            'statuses' => PollStatus::options(),
            'types' => PollType::options(),
        ]);
    }

    public function createPoll(): void
    {
        $this->editingPollId = null;
        $this->showForm = true;
    }

    public function editPoll(int $pollId): void
    {
        $this->editingPollId = $pollId;
        $this->showForm = true;
    }

    public function deletePoll(int $pollId): void
    {
        $poll = Poll::findOrFail($pollId);
        $this->authorizePollManagement($poll);
        app('larapoll')->delete($poll);
    }

    public function activatePoll(int $pollId): void
    {
        $poll = Poll::findOrFail($pollId);
        $this->authorizePollManagement($poll);
        app('larapoll')->activate($poll);
    }

    public function closePoll(int $pollId): void
    {
        $poll = Poll::findOrFail($pollId);
        $this->authorizePollManagement($poll);
        app('larapoll')->close($poll);
    }

    public function cancelPoll(int $pollId): void
    {
        $poll = Poll::findOrFail($pollId);
        $this->authorizePollManagement($poll);
        app('larapoll')->cancel($poll);
    }

    public function duplicatePoll(int $pollId): void
    {
        $poll = Poll::findOrFail($pollId);
        $this->authorizePollManagement($poll);
        app('larapoll')->duplicate($poll);
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

    public function onPollSaved(): void
    {
        $this->showForm = false;
        $this->editingPollId = null;
    }

    public function closeForm(): void
    {
        $this->showForm = false;
        $this->editingPollId = null;
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedTypeFilter(): void
    {
        $this->resetPage();
    }
}
