<?php

return [

    /*
    |--------------------------------------------------------------------------
    | User Model
    |--------------------------------------------------------------------------
    |
    | The fully qualified class name of your User model. This model should
    | use the InteractsWithPolls trait provided by this package.
    |
    */
    'user_model' => \App\Models\User::class,

    /*
    |--------------------------------------------------------------------------
    | Table Names
    |--------------------------------------------------------------------------
    |
    | You can customize the table names used by the package. This is useful
    | if you have existing tables or want to use a different naming convention.
    |
    */
    'tables' => [
        'polls' => 'poll_vote_polls',
        'options' => 'poll_vote_poll_options',
        'votes' => 'poll_vote_poll_votes',
    ],

    /*
    |--------------------------------------------------------------------------
    | Model Classes
    |--------------------------------------------------------------------------
    |
    | You can override the default model classes with your own implementations.
    | This is useful if you need to add custom methods or relationships.
    |
    */
    'models' => [
        'poll' => \Aftandilmmd\PollVote\Models\Poll::class,
        'option' => \Aftandilmmd\PollVote\Models\PollOption::class,
        'vote' => \Aftandilmmd\PollVote\Models\PollVote::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Feature Toggles
    |--------------------------------------------------------------------------
    |
    | Enable or disable specific features of the package.
    |
    */
    'features' => [
        'anonymous_voting' => true,
        'vote_changing' => true,
        'vote_retraction' => true,
        'vote_comments' => true,
        'poll_scheduling' => true,
        'auto_close' => true,
        'auto_open' => true,
        'custom_options' => true,
        'soft_deletes' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Poll Types
    |--------------------------------------------------------------------------
    |
    | Enable or disable specific poll types. Disabled types will not be
    | available when creating new polls.
    |
    */
    'types' => [
        'yes_no' => true,
        'single_choice' => true,
        'multiple_choice' => true,
        'rating' => true,
        'ranked' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Rating Scale
    |--------------------------------------------------------------------------
    |
    | Configure the rating scale for rating-type polls.
    |
    */
    'rating' => [
        'min' => 1,
        'max' => 5,
    ],

    /*
    |--------------------------------------------------------------------------
    | Pagination
    |--------------------------------------------------------------------------
    |
    | Default pagination settings for polls and votes.
    |
    */
    'pagination' => [
        'polls' => 20,
        'votes' => 50,
    ],

    /*
    |--------------------------------------------------------------------------
    | Events
    |--------------------------------------------------------------------------
    |
    | The package dispatches events for various actions. You can disable
    | specific events by setting them to null, or replace them with your
    | own event classes.
    |
    */
    'events' => [
        'poll_created' => \Aftandilmmd\PollVote\Events\PollCreated::class,
        'poll_activated' => \Aftandilmmd\PollVote\Events\PollActivated::class,
        'poll_closed' => \Aftandilmmd\PollVote\Events\PollClosed::class,
        'poll_cancelled' => \Aftandilmmd\PollVote\Events\PollCancelled::class,
        'vote_cast' => \Aftandilmmd\PollVote\Events\VoteCast::class,
        'vote_changed' => \Aftandilmmd\PollVote\Events\VoteChanged::class,
        'vote_retracted' => \Aftandilmmd\PollVote\Events\VoteRetracted::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | API Routes
    |--------------------------------------------------------------------------
    |
    | Enable the optional RESTful API routes for managing polls. Useful for
    | projects that don't use Livewire.
    |
    */
    'api' => [
        'enabled' => false,
        'prefix' => 'api/polls',
        'middleware' => ['api', 'auth:sanctum'],
        'rate_limit' => 60,
    ],

    /*
    |--------------------------------------------------------------------------
    | Livewire Components
    |--------------------------------------------------------------------------
    |
    | Enable or disable Livewire component registration. When enabled, the
    | package will register its Livewire components automatically.
    | Only works if livewire/livewire is installed.
    |
    */
    'livewire' => [
        'enabled' => true,
    ],

];
