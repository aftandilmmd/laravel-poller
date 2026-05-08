[English](README.md) | [Türkçe](README.tr.md) | **Azərbaycanca**

# Laravel Poller

Laravel üçün güclü və çevik sorğu və səsvermə paketi. 5 sorğu növünü, anonim səsverməni, planlanmış sorğuları, səs dəyişdirməni, həm Livewire komponentlərini, həm də RESTful API-ni dəstəkləyir.

## Tələblər

- PHP 8.2+
- Laravel 11, 12 və ya 13 (Laravel 13 PHP 8.3+ tələb edir)

## Quraşdırma

```bash
composer require aftandilmmd/laravel-poller
```

Xidmət provayderi və fasad avtomatik aşkarlanır.

Konfiqurasiya faylını dərc edin:

```bash
php artisan vendor:publish --tag=poller-config
```

Miqrasiyaları dərc edin (istəyə bağlı - miqrasiyalar avtomatik işləyir):

```bash
php artisan vendor:publish --tag=poller-migrations
```

Görünüşləri dərc edin (istəyə bağlı - fərdiləşdirmə üçün):

```bash
php artisan vendor:publish --tag=poller-views
```

Tərcümələri dərc edin (istəyə bağlı - fərdiləşdirmə üçün):

```bash
php artisan vendor:publish --tag=poller-translations
```

Miqrasiyaları işə salın:

```bash
php artisan migrate
```

## Konfiqurasiya

Tam konfiqurasiya seçimləri `config/poller.php` faylındadır:

| Açar | Təsvir | Susmayagörə |
|------|--------|-------------|
| `user_model` | İstifadəçi model sinfi | `App\Models\User` |
| `tables.polls` | Sorğular cədvəlinin adı | `poller_polls` |
| `tables.options` | Seçimlər cədvəlinin adı | `poller_poll_options` |
| `tables.votes` | Səslər cədvəlinin adı | `poller_poll_votes` |
| `features.anonymous_voting` | Anonim səsverməni aktiv et | `true` |
| `features.vote_changing` | Səs dəyişdirməni aktiv et | `true` |
| `features.vote_retraction` | Səs geri çəkməni aktiv et | `true` |
| `features.vote_comments` | Səs şərhlərini aktiv et | `true` |
| `features.auto_close` | Vaxtı bitmiş sorğuları avtomatik bağla | `true` |
| `features.auto_open` | Planlanmış sorğuları avtomatik aç | `true` |
| `features.custom_options` | İstifadəçilərin fərdi seçim əlavə etməsinə icazə ver | `true` |
| `features.poll_scheduling` | Sorğu planlamasını aktiv et (starts_at/ends_at) | `true` |
| `features.soft_deletes` | Sorğularda soft delete aktiv et | `true` |
| `rating.min` | Reytinq şkalasının minimumu | `1` |
| `rating.max` | Reytinq şkalasının maksimumu | `5` |
| `pagination.polls` | Səhifə başına sorğu sayı | `20` |
| `pagination.votes` | Səhifə başına səs sayı | `50` |
| `api.enabled` | REST API marşrutlarını aktiv et | `false` |
| `api.rate_limit` | Dəqiqə başına API sorğu limiti | `60` |

---

## Quraşdırma

### İstənilən modelə sorğu dəstəyi əlavə edin (Pollable)

```php
use Aftandilmmd\Poller\Traits\HasPolls;

class Meeting extends Model
{
    use HasPolls;
}
```

### İstifadəçi modelinə səsvermə qabiliyyəti əlavə edin

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

## Sorğu Növləri

| Növ | Təsvir |
|-----|--------|
| `YesNo` | Sadə bəli/xeyr səsverməsi |
| `SingleChoice` | Bir seçim seçin |
| `MultipleChoice` | Birdən çox seçim seçin (minimum/maksimum məhdudiyyətləri ilə) |
| `Rating` | Seçimləri konfiqurasiya edilə bilən şkala ilə qiymətləndirin (susmayagörə 1-5) |
| `Ranked` | Seçimləri üstünlüyə görə sıralayın |

---

## İstifadə

### Fasad vasitəsilə

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

// Seçimləri yenidən sırala
Poller::reorderOptions($poll, [$optionId3, $optionId1, $optionId2]);

// Duplicate a poll (copies all options)
$newPoll = Poller::duplicate($poll, ['title' => 'Copy of poll']);
```

### Fərdi Seçimlər

Səs verənlərin sorğuya öz seçimlərini əlavə etməsinə icazə verin. Maksimum sayını və kimin əlavə edə biləcəyini idarə edin.

```php
// Fərdi seçimlər aktiv bir sorğu yaradın (maks 5)
$poll = Poller::create([
    'title' => 'Best framework?',
    'type' => 'single_choice',
    'allow_custom_options' => true,
    'max_custom_options' => 5, // null = limitsiz
], $user);

// Fərdi seçim əlavə et (Fasad vasitəsilə)
Poller::addCustomOption($poll, $user, ['title' => 'Mənim təklifim']);

// Fərdi seçim əlavə et (İstifadəçi modeli vasitəsilə)
$user->addCustomOption($poll, ['title' => 'Mənim təklifim']);

// Yardımçı metodlar
$poll->allowsCustomOptions();         // true
$poll->getCustomOptionCount();        // 1
$poll->hasReachedCustomOptionLimit(); // false
$option->isCustom();                  // true
$option->creator;                     // Əlavə edən istifadəçi
```

Səlahiyyəti idarə etmək üçün İstifadəçi modelinizdə `canAddCustomOption()` metodunu əvəz edin:

```php
public function canAddCustomOption(Poll $poll): bool
{
    return $poll->allowsCustomOptions() && $this->is_premium;
}
```

Livewire `PollVote (poller-poll-vote)` vidceti fərdi seçimlər aktivləşdirildikdə və istifadəçi səlahiyyətli olduqda avtomatik olaraq "Öz seçiminizi əlavə edin" daxiletmə sahəsini göstərir.

### Sorğu Modeli vasitəsilə

```php
// Həyat dövrü
$poll->activate();
$poll->close();
$poll->cancel();

// Seçimləri yenidən sırala
$poll->reorderOptions([$optionId3, $optionId1, $optionId2]);

// Dublikat et
$newPoll = $poll->duplicate(['title' => 'Surət']);
```

### Pollable Model vasitəsilə

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

### İstifadəçi Modeli vasitəsilə (InteractsWithPolls trait)

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

## Livewire Komponentləri

Paket Tailwind CSS UI ilə (qaranlıq rejim dəstəklənir) istifadəyə hazır 5 Livewire komponenti daxildir.

> **Qeyd:** Livewire komponentləri istəyə bağlıdır. Livewire olmayan layihələr birbaşa Fasad API və ya REST API istifadə edə bilər.

### Sorğu İdarəedici (Tam CRUD)

```blade
<livewire:poller-poll-manager />

{{-- Scoped to a specific model --}}
<livewire:poller-poll-manager :pollable="$meeting" />
```

Xüsusiyyətlər: Axtarış, status/növə görə süzgəc, yaratma, redaktə, silmə, aktivləşdirmə, bağlama, sorğu dublikatı.

### Sorğu Formu (Yaratma/Redaktə)

```blade
<livewire:poller-poll-form />
<livewire:poller-poll-form :poll-id="$poll->id" />
```

### Sorğu Göstərişi (Tam Görünüş)

```blade
<livewire:poller-poll-display :poll="$poll" />
```

Sorğu məlumatlarını, statistikanı, səsvermə interfeysini, nəticələri və səs tarixçəsi nişanlarını göstərir.

### Sorğu Nəticələri (Analitika)

```blade
<livewire:poller-poll-results :poll="$poll" />
```

Faizlər və lider seçim ilə sütun diaqram nəticələrini göstərir.

### Sorğu Səsi (Kompakt Vidcet)

```blade
<livewire:poller-poll-vote :poll="$poll" />
```

Daxil edilə bilən səsvermə vidceti. Bütün 5 sorğu növünü müvafiq interfeys ilə idarə edir (radio, onay qutusu, reytinq şkalası, sıralama).

### Görünüşlərin Fərdiləşdirilməsi

```bash
php artisan vendor:publish --tag=poller-views
```

Görünüşlər `resources/views/vendor/poller/` qovluğuna dərc ediləcək.

---

## REST API

Konfiqurasiyanızda API-ni aktiv edin:

```php
// config/poller.php
'api' => [
    'enabled' => true,
    'prefix' => 'api/polls',
    'middleware' => ['api', 'auth:sanctum'],
    'rate_limit' => 60, // dəqiqə başına sorğu (null ilə söndür)
],
```

Bütün dəyişiklik son nöqtələri (yeniləmə, silmə, həyat dövrü, seçim idarəetməsi) sahib yoxlaması tətbiq edir. İstifadəçi modeliniz `InteractsWithPolls` trait-ini istifadə edirsə, səlahiyyətləndirmə üçün `canManagePoll()` metodu istifadə edilir.

API cavabları ardıcıl JSON formatlaşdırması üçün Eloquent API Resources istifadə edir.

### Son Nöqtələr

| Metod | Son Nöqtə | Təsvir |
|-------|-----------|--------|
| `GET` | `/api/polls` | Sorğuları siyahıla (süzgəclərlə) |
| `POST` | `/api/polls` | Sorğu yarat |
| `GET` | `/api/polls/{poll}` | Sorğunu göstər |
| `PUT` | `/api/polls/{poll}` | Sorğunu yenilə |
| `DELETE` | `/api/polls/{poll}` | Sorğunu sil |
| `POST` | `/api/polls/{poll}/activate` | Aktivləşdir |
| `POST` | `/api/polls/{poll}/close` | Bağla |
| `POST` | `/api/polls/{poll}/cancel` | Ləğv et |
| `POST` | `/api/polls/{poll}/duplicate` | Dublikat et |
| `POST` | `/api/polls/{poll}/options` | Seçim əlavə et |
| `PUT` | `/api/polls/{poll}/options/{option}` | Seçimi yenilə |
| `DELETE` | `/api/polls/{poll}/options/{option}` | Seçimi sil |
| `POST` | `/api/polls/{poll}/options/reorder` | Seçimləri yenidən sırala |
| `POST` | `/api/polls/{poll}/vote` | Səs ver |
| `PUT` | `/api/polls/{poll}/vote` | Səsi dəyişdir |
| `DELETE` | `/api/polls/{poll}/vote` | Səsi geri çək |
| `GET` | `/api/polls/{poll}/results` | Nəticələri al |
| `GET` | `/api/polls/{poll}/votes` | Səsləri siyahıla |

### Nümunə: Səs Vermə

```bash
curl -X POST /api/polls/1/vote \
  -H "Authorization: Bearer $TOKEN" \
  -d '{"options": [3], "comment": "My pick"}'
```

---

## Əmrlər

### Planlı Əmrlər

Avtomatik sorğu həyat dövrü idarəetməsi üçün planlaşdırıcınıza əlavə edin:

```php
// routes/console.php or bootstrap/app.php
Schedule::command('poller:auto-open')->everyMinute();
Schedule::command('poller:auto-close')->everyMinute();
```

- `poller:auto-open` -- `starts_at` vaxtı keçmiş qaralama sorğularını aktivləşdirir
- `poller:auto-close` -- `ends_at` vaxtı keçmiş aktiv sorğuları bağlayır

### Texniki Xidmət Əmrləri

```bash
# Bütün seçim səs sayılarını faktiki səs qeydlərindən yenidən hesabla
php artisan poller:reconcile-counts
```

---

## Hadisələr

Bütün hadisələr `config/poller.php` vasitəsilə konfiqurasiya edilə bilir. Söndürmək üçün `null` təyin edin.

| Hadisə | Məlumat |
|--------|---------|
| `PollCreated` | Sorğu, yaradan |
| `PollActivated` | Sorğu |
| `PollClosed` | Sorğu |
| `PollCancelled` | Sorğu |
| `VoteCast` | Sorğu, səsverən, səslər |
| `VoteChanged` | Sorğu, səsverən, köhnəSəslər, yeniSəslər |
| `VoteRetracted` | Sorğu, səsverən |

```php
// Listen to events
Event::listen(VoteCast::class, function ($event) {
    // $event->poll, $event->voter, $event->votes
});
```

---

## Xəta İdarəetməsi

Bütün səsvermə xətaları tipli istisnalar atır:

```php
use Aftandilmmd\Poller\Exceptions\PollClosedException;
use Aftandilmmd\Poller\Exceptions\AlreadyVotedException;
use Aftandilmmd\Poller\Exceptions\InvalidSelectionException;
use Aftandilmmd\Poller\Exceptions\UnauthorizedVoteException;
use Aftandilmmd\Poller\Exceptions\CustomOptionException;

try {
    Poller::castVote($poll, $user, $optionId);
} catch (PollClosedException $e) {
    // Sorğu səs qəbul etmir
} catch (AlreadyVotedException $e) {
    // İstifadəçi artıq səs verib (və səs dəyişdirmə söndürülüb)
} catch (InvalidSelectionException $e) {
    // Yanlış sayda seçim və ya etibarsız seçim
} catch (UnauthorizedVoteException $e) {
    // İstifadəçinin canVote() metodu false qaytardı
} catch (CustomOptionException $e) {
    // Fərdi seçimlərə icazə verilmir, limitə çatılıb və ya səlahiyyətsiz
}
```

---

## Enumlar

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

## Genişləndirmə

### Fərdi Modellər

Konfiqurasiyada model siniflərini əvəz edin:

```php
'models' => [
    'poll' => App\Models\CustomPoll::class,
    'option' => App\Models\CustomPollOption::class,
    'vote' => App\Models\CustomPoller::class,
],
```

### Fərdi Hadisələr

Hadisə siniflərini əvəz edin və ya söndürün:

```php
'events' => [
    'vote_cast' => App\Events\CustomVoteCast::class,
    'poll_created' => null, // Disabled
],
```

---

## Tərcümələr

Paket İngiliscə, Türkcə və Azərbaycanca tərcümələr daxildir. Fərdiləşdirmək və ya yeni dil əlavə etmək üçün:

```bash
php artisan vendor:publish --tag=poller-translations
```

Tərcümə faylları `lang/vendor/poller/` qovluğuna dərc edilir.

---

## Test

```bash
php artisan test --filter=PollVote
```

---

## Lisenziya

MIT
