**English** | [Türkçe](README.tr.md) | [Azərbaycanca](README.az.md)

# Laravel Poller

A powerful, flexible poll and voting package for Laravel. Supports 5 poll types, anonymous voting, scheduled polls, vote changing, and both Livewire components and a RESTful API.

## Requirements

- PHP 8.2+
- Laravel 11, 12, or 13 (Laravel 13 requires PHP 8.3+)

## Installation

```bash
composer require aftandilmmd/laravel-poller
```

The service provider and facade are auto-discovered.

Publish the config file:

```bash
php artisan vendor:publish --tag=poller-config
```

Publish migrations (optional - migrations run automatically):

```bash
php artisan vendor:publish --tag=poller-migrations
```

Publish views (optional - for customization):

```bash
php artisan vendor:publish --tag=poller-views
```

Publish translations (optional - for customization):

```bash
php artisan vendor:publish --tag=poller-translations
```

Run migrations:

```bash
php artisan migrate
```

## Configuration

Full config options in `config/poller.php`:

| Key | Description | Default |
|-----|-------------|---------|
| `user_model` | Your User model class | `App\Models\User` |
| `tables.polls` | Polls table name | `poller_polls` |
| `tables.options` | Options table name | `poller_poll_options` |
| `tables.votes` | Votes table name | `poller_poll_votes` |
| `features.anonymous_voting` | Enable anonymous voting | `true` |
| `features.vote_changing` | Enable vote changing | `true` |
| `features.vote_retraction` | Enable vote retraction | `true` |
| `features.vote_comments` | Enable vote comments | `true` |
| `features.auto_close` | Auto-close expired polls | `true` |
| `features.auto_open` | Auto-open scheduled polls | `true` |
| `features.custom_options` | Allow users to add custom options | `true` |
| `features.poll_scheduling` | Enable poll scheduling (starts_at/ends_at) | `true` |
| `features.soft_deletes` | Enable soft deletes on polls | `true` |
| `rating.min` | Rating scale minimum | `1` |
| `rating.max` | Rating scale maximum | `5` |
| `pagination.polls` | Polls per page | `20` |
| `pagination.votes` | Votes per page | `50` |
| `api.enabled` | Enable REST API routes | `false` |
| `api.rate_limit` | API requests per minute | `60` |

---

## Setup

### Add poll support to any model (Pollable)

```php
use Aftandilmmd\Poller\Traits\HasPolls;

class Meeting extends Model
{
    use HasPolls;
}
```

### Add voting capabilities to User model

```php
use Aftandilmmd\Poller\Traits\InteractsWithPolls;

class User extends Authenticatable
{
    use InteractsWithPolls;

    // Override for custom authorization:
    public function canCreatePoll(): bool
    {
        return $this->is_admin;
    }

    public function canVote(Poll $poll): bool
    {
        return $poll->isVotingOpen() && $this->hasActiveSubscription();
    }

    public function canAddCustomOption(Poll $poll): bool
    {
        return $poll->allowsCustomOptions() && $this->is_premium;
    }

    public function canManagePoll(Poll $poll): bool
    {
        return $poll->created_by === $this->id || $this->is_admin;
    }
}
```

---

## Poll Types

| Type | Description |
|------|-------------|
| `YesNo` | Simple yes/no voting |
| `SingleChoice` | Select one option |
| `MultipleChoice` | Select multiple options (with min/max constraints) |
| `Rating` | Rate options on a configurable scale (default 1-5) |
| `Ranked` | Rank options by preference |

---

## Usage

### Via Facade

```php
use Aftandilmmd\Poller\Facades\Poller;

// Create a poll
$poll = Poller::create([
    'title' => 'Best framework?',
    'type' => 'single_choice',
    'is_anonymous' => false,
    'show_results_before_close' => true,
    'allow_vote_change' => true,
], $user);

// Add options
Poller::addOption($poll, ['title' => 'Laravel']);
Poller::addOption($poll, ['title' => 'Django']);
Poller::addOption($poll, ['title' => 'Rails']);

// Activate the poll
Poller::activate($poll);

// Cast a vote
Poller::castVote($poll, $user, $optionId);

// Cast vote with comment
Poller::castVote($poll, $user, $optionId, ['comment' => 'Great choice!']);

// Change a vote
Poller::changeVote($poll, $user, $newOptionId);

// Retract a vote
Poller::retractVote($poll, $user);

// Get results
$results = Poller::getResults($poll);
// [['option_id' => 1, 'title' => 'Laravel', 'votes_count' => 15, 'percentage' => 75.0], ...]

$detailed = Poller::getDetailedResults($poll);
// ['poll' => ..., 'total_votes' => 20, 'unique_voters' => 18, 'options' => [...], 'leading_option' => ...]

// Lifecycle
Poller::close($poll);
Poller::cancel($poll);

// Reorder options
Poller::reorderOptions($poll, [$optionId3, $optionId1, $optionId2]);

// Duplicate a poll (copies all options)
$newPoll = Poller::duplicate($poll, ['title' => 'Copy of poll']);
```

### Custom Options

Allow voters to add their own options to a poll. Control the maximum number and who can add them.

```php
// Create a poll with custom options enabled (max 5)
$poll = Poller::create([
    'title' => 'Best framework?',
    'type' => 'single_choice',
    'allow_custom_options' => true,
    'max_custom_options' => 5, // null = unlimited
], $user);

// Add a custom option (via Facade)
Poller::addCustomOption($poll, $user, ['title' => 'My suggestion']);

// Add a custom option (via User model)
$user->addCustomOption($poll, ['title' => 'My suggestion']);

// Check helpers
$poll->allowsCustomOptions();         // true
$poll->getCustomOptionCount();        // 1
$poll->hasReachedCustomOptionLimit(); // false
$option->isCustom();                  // true
$option->creator;                     // User who added it
```

Override `canAddCustomOption()` in your User model to control authorization:

```php
public function canAddCustomOption(Poll $poll): bool
{
    return $poll->allowsCustomOptions() && $this->is_premium;
}
```

The Livewire `PollVote (poller-poll-vote)` widget automatically shows a "Add your own option" input when custom options are enabled and the user is authorized.

### Via Poll Model

```php
// Lifecycle
$poll->activate();
$poll->close();
$poll->cancel();

// Reorder options
$poll->reorderOptions([$optionId3, $optionId1, $optionId2]);

// Duplicate
$newPoll = $poll->duplicate(['title' => 'Copy']);
```

### Via Pollable Model

```php
// Create a poll attached to a meeting
$poll = $meeting->createPoll([
    'title' => 'Meeting agenda vote',
    'type' => 'multiple_choice',
    'min_selections' => 1,
    'max_selections' => 3,
], $user);

// Get polls
$meeting->polls;
$meeting->activePolls;
$meeting->closedPolls;
$meeting->hasPollsInProgress();
```

### Via User Model (InteractsWithPolls trait)

```php
$user->vote($poll, $optionId);
$user->changeVote($poll, $newOptionId);
$user->retractVote($poll);
$user->hasVotedOn($poll);     // true/false
$user->getVotesFor($poll);    // Collection of PollVote
$user->createdPolls;           // HasMany
$user->pollVotes;              // HasMany
```

---

## Livewire Components

The package includes 5 ready-to-use Livewire components with full Tailwind CSS UI (dark mode supported).

> **Note:** Livewire components are optional. Projects without Livewire can use the Facade API or REST API directly.

### Poll Manager (Full CRUD)

```blade
<livewire:poller-poll-manager />

{{-- Scoped to a specific model --}}
<livewire:poller-poll-manager :pollable="$meeting" />
```

Features: Search, filter by status/type, create, edit, delete, activate, close, duplicate polls.

### Poll Form (Create/Edit)

```blade
<livewire:poller-poll-form />
<livewire:poller-poll-form :poll-id="$poll->id" />
```

### Poll Display (Full View)

```blade
<livewire:poller-poll-display :poll="$poll" />
```

Shows poll info, stats, voting UI, results, and vote history tabs.

### Poll Results (Analytics)

```blade
<livewire:poller-poll-results :poll="$poll" />
```

Displays bar chart results with percentages and leading option.

### Vote Widget (Compact)

```blade
<livewire:poller-poll-vote :poll="$poll" />
```

Embeddable voting widget. Handles all 5 poll types with the appropriate UI (radio, checkbox, rating scale, ranking).

### Customizing Views

```bash
php artisan vendor:publish --tag=poller-views
```

Views will be published to `resources/views/vendor/poller/`.

---

## REST API

Enable the API in your config:

```php
// config/poller.php
'api' => [
    'enabled' => true,
    'prefix' => 'api/polls',
    'middleware' => ['api', 'auth:sanctum'],
    'rate_limit' => 60, // requests per minute (null to disable)
],
```

All mutation endpoints (update, delete, lifecycle actions, option management) enforce ownership checks. If your User model uses the `InteractsWithPolls` trait, the `canManagePoll()` method is used for authorization.

API responses use Eloquent API Resources for consistent JSON formatting.

### Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/api/polls` | List polls (with filters) |
| `POST` | `/api/polls` | Create poll |
| `GET` | `/api/polls/{poll}` | Show poll |
| `PUT` | `/api/polls/{poll}` | Update poll |
| `DELETE` | `/api/polls/{poll}` | Delete poll |
| `POST` | `/api/polls/{poll}/activate` | Activate |
| `POST` | `/api/polls/{poll}/close` | Close |
| `POST` | `/api/polls/{poll}/cancel` | Cancel |
| `POST` | `/api/polls/{poll}/duplicate` | Duplicate |
| `POST` | `/api/polls/{poll}/options` | Add option |
| `PUT` | `/api/polls/{poll}/options/{option}` | Update option |
| `DELETE` | `/api/polls/{poll}/options/{option}` | Remove option |
| `POST` | `/api/polls/{poll}/options/reorder` | Reorder options |
| `POST` | `/api/polls/{poll}/vote` | Cast vote |
| `PUT` | `/api/polls/{poll}/vote` | Change vote |
| `DELETE` | `/api/polls/{poll}/vote` | Retract vote |
| `GET` | `/api/polls/{poll}/results` | Get results |
| `GET` | `/api/polls/{poll}/votes` | List votes |

### Example: Cast a Vote

```bash
curl -X POST /api/polls/1/vote \
  -H "Authorization: Bearer $TOKEN" \
  -d '{"options": [3], "comment": "My pick"}'
```

---

## Commands

### Scheduled Commands

Add to your scheduler for automatic poll lifecycle management:

```php
// routes/console.php or bootstrap/app.php
Schedule::command('poller:auto-open')->everyMinute();
Schedule::command('poller:auto-close')->everyMinute();
```

- `poller:auto-open` -- Activates draft polls whose `starts_at` has passed
- `poller:auto-close` -- Closes active polls whose `ends_at` has passed

### Maintenance Commands

```bash
# Recalculate all option vote counts from actual vote records
php artisan poller:reconcile-counts
```

---

## Events

All events are configurable via `config/poller.php`. Set to `null` to disable.

| Event | Payload |
|-------|---------|
| `PollCreated` | Poll, creator |
| `PollActivated` | Poll |
| `PollClosed` | Poll |
| `PollCancelled` | Poll |
| `VoteCast` | Poll, voter, votes |
| `VoteChanged` | Poll, voter, oldVotes, newVotes |
| `VoteRetracted` | Poll, voter |

```php
// Listen to events
Event::listen(VoteCast::class, function ($event) {
    // $event->poll, $event->voter, $event->votes
});
```

---

## Advanced Features

All advanced features below are **opt-in** via `config/poller.php`. Defaults keep behavior unchanged.

### Result Caching

Cache poll results to avoid recomputing on every request. Cache is invalidated automatically when votes are cast, changed, or retracted.

```php
// config/poller.php
'cache' => [
    'enabled' => true,
    'store' => null,         // null = default cache store
    'ttl' => 60,             // seconds
    'prefix' => 'poller',
],
```

```php
$poll->getResultsAsPercentages();   // first call hits DB, subsequent calls hit cache
$poll->flushResultsCache();         // manual invalidation
```

### Broadcasting

Make poll/vote events broadcast over Laravel Echo / WebSockets. Channel name pattern: `{prefix}.{pollId}`.

```php
// config/poller.php
'broadcasting' => [
    'enabled' => true,
    'channel' => 'private',          // private | presence | public
    'channel_prefix' => 'poller.poll',
],
```

```js
// resources/js — listen on the frontend
Echo.private(`poller.poll.${pollId}`)
    .listen('VoteCast', (e) => updateChart(e.poll));
```

### Voter Rate Limiting

Limit how many votes a single voter can cast across all polls in a sliding window. Throws `VoterRateLimitException` when exceeded.

```php
// config/poller.php
'voter_rate_limit' => [
    'enabled' => true,
    'max_votes' => 30,
    'per_minutes' => 60,
],
```

### Translatable Content

Store poll/option `title` and `description` as JSON locale maps. Returns the value for `app()->getLocale()` automatically, falls back to `fallback_locale`.

```php
// config/poller.php
'translatable' => [
    'enabled' => true,
    'fallback_locale' => 'en',
],
```

```php
// Create with translations
Poller::create([
    'title' => ['en' => 'Best framework?', 'tr' => 'En iyi framework?', 'az' => 'Ən yaxşı framework?'],
], $user);

// Read in current locale
app()->setLocale('tr');
$poll->title;                          // "En iyi framework?"

// Translation helpers
$poll->translate('title', 'az');       // "Ən yaxşı framework?"
$poll->setTranslation('title', 'tr', 'Yeni başlık')->save();
$poll->getTranslations('title');       // ['en' => '...', 'tr' => '...', 'az' => '...']
```

### Query Scopes

Search and filter polls with chainable scopes:

```php
use Aftandilmmd\Poller\Models\Poll;
use Aftandilmmd\Poller\Enums\PollStatus;
use Aftandilmmd\Poller\Enums\PollType;

Poll::query()
    ->search('framework')                       // matches title or description
    ->ofStatus(PollStatus::Active)              // enum or string
    ->ofType(PollType::SingleChoice)
    ->createdBy($user->id)
    ->withinDateRange(now()->subMonth(), now())
    ->get();
```

---

## Error Handling

All voting errors throw typed exceptions:

```php
use Aftandilmmd\Poller\Exceptions\PollClosedException;
use Aftandilmmd\Poller\Exceptions\AlreadyVotedException;
use Aftandilmmd\Poller\Exceptions\InvalidSelectionException;
use Aftandilmmd\Poller\Exceptions\UnauthorizedVoteException;
use Aftandilmmd\Poller\Exceptions\CustomOptionException;

try {
    Poller::castVote($poll, $user, $optionId);
} catch (PollClosedException $e) {
    // Poll is not accepting votes
} catch (AlreadyVotedException $e) {
    // User already voted (and vote_change is disabled)
} catch (InvalidSelectionException $e) {
    // Wrong number of selections or invalid option
} catch (UnauthorizedVoteException $e) {
    // User's canVote() returned false
} catch (CustomOptionException $e) {
    // Custom options not allowed, limit reached, or unauthorized
}
```

---

## Enums

```php
use Aftandilmmd\Poller\Enums\PollType;
use Aftandilmmd\Poller\Enums\PollStatus;

PollType::SingleChoice->value;   // "single_choice"
PollType::SingleChoice->label(); // "Single Choice"
PollType::SingleChoice->color(); // "green"
PollType::options();             // ["yes_no" => "Yes/No", ...]
PollType::enabled();             // Only config-enabled types

PollStatus::Active->value;      // "active"
PollStatus::Active->label();    // "Active"
PollStatus::Active->color();    // "green"
```

---

## Extending

### Custom Models

Override model classes in config:

```php
'models' => [
    'poll' => App\Models\CustomPoll::class,
    'option' => App\Models\CustomPollOption::class,
    'vote' => App\Models\CustomPoller::class,
],
```

### Custom Events

Replace event classes or disable them:

```php
'events' => [
    'vote_cast' => App\Events\CustomVoteCast::class,
    'poll_created' => null, // Disabled
],
```

---

## Translations

The package includes translations for English, Turkish, and Azerbaijani. To customize or add new languages:

```bash
php artisan vendor:publish --tag=poller-translations
```

Translation files will be published to `lang/vendor/poller/`.

---

## Testing

```bash
composer install
vendor/bin/pest
```

---

## Roadmap

### Shipped

- [x] Core CRUD with 5 poll types (yes/no, single, multiple, rating, ranked)
- [x] Anonymous voting, vote changing, vote retraction
- [x] Scheduled polls with auto-open / auto-close commands
- [x] User-suggested custom options with limits
- [x] Vote comments and required-comment polls
- [x] Result percentages, leading option, detailed results export
- [x] REST API (18 endpoints)
- [x] Livewire components (Manager, Form, Display, Vote, Results)
- [x] Trait-based authorization (`InteractsWithPolls`, `HasPolls`)
- [x] 7 lifecycle/voting events with broadcasting support
- [x] Pollable morph (attach polls to any model)
- [x] Soft deletes
- [x] Result caching with auto-invalidation
- [x] Voter rate limiting (cross-poll sliding window)
- [x] Translatable title/description (opt-in JSON locale map)
- [x] Query scopes: `search`, `ofStatus`, `ofType`, `createdBy`, `withinDateRange`
- [x] API filter parameters (`search`, `status`, `type`, `created_by`, `from`, `to`)
- [x] API returns `429` on voter rate limit
- [x] Localized exception messages (en, tr, az)
- [x] Laravel 11, 12, 13 support

### Considered for future

- [ ] Translatable form fields in Livewire `PollForm` (multi-locale inputs)
- [ ] `PollResource@withTranslations` for API multi-locale output
- [ ] CSV / JSON export beyond `array`
- [ ] IP-based vote tracking (anonymous spam protection)
- [ ] Built-in tags / categories
- [ ] First-party Filament / Nova plugin

### Out of scope

These belong in user code or sibling packages, not this one:

- [ ] Notifications (mail / database / broadcast on poll events) — wire your own listeners
- [ ] Captcha / spam middleware — apply at the route level
- [ ] Webhooks — listen to events and POST yourself
- [ ] Charts / analytics dashboard — render from `getDetailedResults()` data
- [ ] Audit log — use [`spatie/laravel-activitylog`](https://github.com/spatie/laravel-activitylog) on the events
- [ ] Short URLs / QR codes — use a dedicated package

---

## License

MIT
