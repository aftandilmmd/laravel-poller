[English](README.md) | **Türkçe** | [Azərbaycanca](README.az.md)

# Laravel Poller

Laravel için güçlü ve esnek bir anket ve oylama paketi. 5 anket türünü, anonim oylamayı, zamanlanmış anketleri, oy değiştirmeyi ve hem Livewire bileşenleri hem de RESTful API destekler.

## Gereksinimler

- PHP 8.2+
- Laravel 11, 12 veya 13 (Laravel 13 PHP 8.3+ gerektirir)

## Kurulum

```bash
composer require aftandilmmd/laravel-poller
```

Servis sağlayıcı ve facade otomatik olarak keşfedilir.

Yapılandırma dosyasını yayınlayın:

```bash
php artisan vendor:publish --tag=poller-config
```

Migration dosyalarını yayınlayın (isteğe bağlı - migration'lar otomatik çalışır):

```bash
php artisan vendor:publish --tag=poller-migrations
```

Görünümleri yayınlayın (isteğe bağlı - özelleştirme için):

```bash
php artisan vendor:publish --tag=poller-views
```

Çevirileri yayınlayın (isteğe bağlı - özelleştirme için):

```bash
php artisan vendor:publish --tag=poller-translations
```

Migration'ları çalıştırın:

```bash
php artisan migrate
```

## Yapılandırma

Tüm yapılandırma seçenekleri `config/poller.php` dosyasında:

| Anahtar | Açıklama | Varsayılan |
|---------|----------|------------|
| `user_model` | Kullanıcı model sınıfı | `App\Models\User` |
| `tables.polls` | Anketler tablo adı | `poller_polls` |
| `tables.options` | Seçenekler tablo adı | `poller_poll_options` |
| `tables.votes` | Oylar tablo adı | `poller_poll_votes` |
| `features.anonymous_voting` | Anonim oylamayı etkinleştir | `true` |
| `features.vote_changing` | Oy değiştirmeyi etkinleştir | `true` |
| `features.vote_retraction` | Oy geri çekmeyi etkinleştir | `true` |
| `features.vote_comments` | Oy yorumlarını etkinleştir | `true` |
| `features.auto_close` | Süresi dolan anketleri otomatik kapat | `true` |
| `features.auto_open` | Zamanlanmış anketleri otomatik aç | `true` |
| `features.custom_options` | Kullanıcıların özel seçenek eklemesine izin ver | `true` |
| `features.poll_scheduling` | Anket zamanlamasını etkinleştir (starts_at/ends_at) | `true` |
| `features.soft_deletes` | Anketlerde soft delete etkinleştir | `true` |
| `rating.min` | Derecelendirme ölçeği minimumu | `1` |
| `rating.max` | Derecelendirme ölçeği maksimumu | `5` |
| `pagination.polls` | Sayfa başına anket sayısı | `20` |
| `pagination.votes` | Sayfa başına oy sayısı | `50` |
| `api.enabled` | REST API rotalarını etkinleştir | `false` |
| `api.rate_limit` | Dakika başına API istek limiti | `60` |

---

## Kurulum

### Herhangi bir modele anket desteği ekleyin (Pollable)

```php
use Aftandilmmd\Poller\Traits\HasPolls;

class Meeting extends Model
{
    use HasPolls;
}
```

### Kullanıcı modeline oylama yeteneği ekleyin

```php
use Aftandilmmd\Poller\Traits\InteractsWithPolls;

class User extends Authenticatable
{
    use InteractsWithPolls;

    // Özel yetkilendirme için geçersiz kılın:
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

## Anket Türleri

| Tür | Açıklama |
|-----|----------|
| `YesNo` | Basit evet/hayır oylaması |
| `SingleChoice` | Tek seçenek seçimi |
| `MultipleChoice` | Birden fazla seçenek seçimi (min/maks kısıtlamalı) |
| `Rating` | Seçenekleri yapılandırılabilir ölçekte derecelendirme (varsayılan 1-5) |
| `Ranked` | Seçenekleri tercih sırasına göre sıralama |

---

## Kullanım

### Facade ile

```php
use Aftandilmmd\Poller\Facades\Poller;

// Anket oluştur
$poll = Poller::create([
    'title' => 'En iyi framework?',
    'type' => 'single_choice',
    'is_anonymous' => false,
    'show_results_before_close' => true,
    'allow_vote_change' => true,
], $user);

// Seçenek ekle
Poller::addOption($poll, ['title' => 'Laravel']);
Poller::addOption($poll, ['title' => 'Django']);
Poller::addOption($poll, ['title' => 'Rails']);

// Anketi etkinleştir
Poller::activate($poll);

// Oy ver
Poller::castVote($poll, $user, $optionId);

// Yorumla oy ver
Poller::castVote($poll, $user, $optionId, ['comment' => 'Harika seçim!']);

// Oy değiştir
Poller::changeVote($poll, $user, $newOptionId);

// Oy geri çek
Poller::retractVote($poll, $user);

// Sonuçları al
$results = Poller::getResults($poll);
// [['option_id' => 1, 'title' => 'Laravel', 'votes_count' => 15, 'percentage' => 75.0], ...]

$detailed = Poller::getDetailedResults($poll);
// ['poll' => ..., 'total_votes' => 20, 'unique_voters' => 18, 'options' => [...], 'leading_option' => ...]

// Yaşam döngüsü
Poller::close($poll);
Poller::cancel($poll);

// Seçenekleri yeniden sırala
Poller::reorderOptions($poll, [$optionId3, $optionId1, $optionId2]);

// Anketi kopyala (tüm seçenekleriyle)
$newPoll = Poller::duplicate($poll, ['title' => 'Anketin kopyası']);
```

### Özel Seçenekler

Oy verenlerin ankete kendi seçeneklerini eklemesine izin verin. Maksimum sayıyı ve kimlerin ekleyebileceğini kontrol edin.

```php
// Özel seçenekler etkin bir anket oluşturun (maks 5)
$poll = Poller::create([
    'title' => 'En iyi framework?',
    'type' => 'single_choice',
    'allow_custom_options' => true,
    'max_custom_options' => 5, // null = sınırsız
], $user);

// Özel seçenek ekle (Facade ile)
Poller::addCustomOption($poll, $user, ['title' => 'Benim önerim']);

// Özel seçenek ekle (Kullanıcı modeli ile)
$user->addCustomOption($poll, ['title' => 'Benim önerim']);

// Yardımcı metodlar
$poll->allowsCustomOptions();         // true
$poll->getCustomOptionCount();        // 1
$poll->hasReachedCustomOptionLimit(); // false
$option->isCustom();                  // true
$option->creator;                     // Ekleyen kullanıcı
```

Yetkilendirmeyi kontrol etmek için Kullanıcı modelinizde `canAddCustomOption()` metodunu geçersiz kılın:

```php
public function canAddCustomOption(Poll $poll): bool
{
    return $poll->allowsCustomOptions() && $this->is_premium;
}
```

Livewire `PollVote (poller-poll-vote)` widget'ı, özel seçenekler etkinleştirildiğinde ve kullanıcı yetkili olduğunda otomatik olarak "Kendi seçeneğinizi ekleyin" giriş alanını gösterir.

### Anket Modeli ile

```php
// Yaşam döngüsü
$poll->activate();
$poll->close();
$poll->cancel();

// Seçenekleri yeniden sırala
$poll->reorderOptions([$optionId3, $optionId1, $optionId2]);

// Kopyala
$newPoll = $poll->duplicate(['title' => 'Kopya']);
```

### Pollable Model ile

```php
// Toplantıya bağlı bir anket oluştur
$poll = $meeting->createPoll([
    'title' => 'Toplantı gündemi oylaması',
    'type' => 'multiple_choice',
    'min_selections' => 1,
    'max_selections' => 3,
], $user);

// Anketleri al
$meeting->polls;
$meeting->activePolls;
$meeting->closedPolls;
$meeting->hasPollsInProgress();
```

### Kullanıcı Modeli ile (InteractsWithPolls trait)

```php
$user->vote($poll, $optionId);
$user->changeVote($poll, $newOptionId);
$user->retractVote($poll);
$user->hasVotedOn($poll);     // true/false
$user->getVotesFor($poll);    // PollVote Collection
$user->createdPolls;           // HasMany
$user->pollVotes;              // HasMany
```

---

## Livewire Bileşenleri

Paket, tam Tailwind CSS arayüzü ile (karanlık mod destekli) kullanıma hazır 5 Livewire bileşeni içerir.

> **Not:** Livewire bileşenleri isteğe bağlıdır. Livewire kullanmayan projeler Facade API veya REST API'yi doğrudan kullanabilir.

### Anket Yöneticisi (Tam CRUD)

```blade
<livewire:poller-poll-manager />

{{-- Belirli bir modele kapsamlı --}}
<livewire:poller-poll-manager :pollable="$meeting" />
```

Özellikler: Arama, duruma/türe göre filtreleme, oluşturma, düzenleme, silme, etkinleştirme, kapatma, anket kopyalama.

### Anket Formu (Oluştur/Düzenle)

```blade
<livewire:poller-poll-form />
<livewire:poller-poll-form :poll-id="$poll->id" />
```

### Anket Görünümü (Tam Görüntüleme)

```blade
<livewire:poller-poll-display :poll="$poll" />
```

Anket bilgilerini, istatistikleri, oylama arayüzünü, sonuçları ve oy geçmişi sekmelerini gösterir.

### Anket Sonuçları (Analiz)

```blade
<livewire:poller-poll-results :poll="$poll" />
```

Yüzdelikler ve önde gelen seçenek ile çubuk grafik sonuçlarını gösterir.

### Anket Oyu (Kompakt Widget)

```blade
<livewire:poller-poll-vote :poll="$poll" />
```

Gömülür oylama widget'ı. Uygun arayüz ile (radyo, onay kutusu, derecelendirme ölçeği, sıralama) tüm 5 anket türünü destekler.

### Görünümleri Özelleştirme

```bash
php artisan vendor:publish --tag=poller-views
```

Görünümler `resources/views/vendor/poller/` dizinine yayınlanacaktır.

---

## REST API

Yapılandırma dosyasında API'yi etkinleştirin:

```php
// config/poller.php
'api' => [
    'enabled' => true,
    'prefix' => 'api/polls',
    'middleware' => ['api', 'auth:sanctum'],
    'rate_limit' => 60, // dakika başına istek (null ile devre dışı)
],
```

Tüm değişiklik uç noktaları (güncelleme, silme, yaşam döngüsü, seçenek yönetimi) sahiplik kontrolü uygular. Kullanıcı modeliniz `InteractsWithPolls` trait'ini kullanıyorsa, yetkilendirme için `canManagePoll()` metodu kullanılır.

API yanıtları tutarlı JSON formatlaması için Eloquent API Resources kullanır.

### Uç Noktalar

| Metot | Uç Nokta | Açıklama |
|-------|----------|----------|
| `GET` | `/api/polls` | Anketleri listele (filtreli) |
| `POST` | `/api/polls` | Anket oluştur |
| `GET` | `/api/polls/{poll}` | Anket göster |
| `PUT` | `/api/polls/{poll}` | Anket güncelle |
| `DELETE` | `/api/polls/{poll}` | Anket sil |
| `POST` | `/api/polls/{poll}/activate` | Etkinleştir |
| `POST` | `/api/polls/{poll}/close` | Kapat |
| `POST` | `/api/polls/{poll}/cancel` | İptal et |
| `POST` | `/api/polls/{poll}/duplicate` | Kopyala |
| `POST` | `/api/polls/{poll}/options` | Seçenek ekle |
| `PUT` | `/api/polls/{poll}/options/{option}` | Seçenek güncelle |
| `DELETE` | `/api/polls/{poll}/options/{option}` | Seçenek kaldır |
| `POST` | `/api/polls/{poll}/options/reorder` | Seçenekleri yeniden sırala |
| `POST` | `/api/polls/{poll}/vote` | Oy ver |
| `PUT` | `/api/polls/{poll}/vote` | Oy değiştir |
| `DELETE` | `/api/polls/{poll}/vote` | Oy geri çek |
| `GET` | `/api/polls/{poll}/results` | Sonuçları getir |
| `GET` | `/api/polls/{poll}/votes` | Oyları listele |

### Örnek: Oy Verme

```bash
curl -X POST /api/polls/1/vote \
  -H "Authorization: Bearer $TOKEN" \
  -d '{"options": [3], "comment": "Benim seçimim"}'
```

---

## Komutlar

### Zamanlanmış Komutlar

Otomatik anket yaşam döngüsü yönetimi için zamanlayıcınıza ekleyin:

```php
// routes/console.php veya bootstrap/app.php
Schedule::command('poller:auto-open')->everyMinute();
Schedule::command('poller:auto-close')->everyMinute();
```

- `poller:auto-open` -- `starts_at` tarihi geçmiş taslak anketleri etkinleştirir
- `poller:auto-close` -- `ends_at` tarihi geçmiş aktif anketleri kapatır

### Bakım Komutları

```bash
# Tüm seçenek oy sayılarını gerçek oy kayıtlarından yeniden hesapla
php artisan poller:reconcile-counts
```

---

## Olaylar

Tüm olaylar `config/poller.php` üzerinden yapılandırılabilir. Devre dışı bırakmak için `null` olarak ayarlayın.

| Olay | Veri |
|------|------|
| `PollCreated` | Anket, oluşturucu |
| `PollActivated` | Anket |
| `PollClosed` | Anket |
| `PollCancelled` | Anket |
| `VoteCast` | Anket, oy veren, oylar |
| `VoteChanged` | Anket, oy veren, eskiOylar, yeniOylar |
| `VoteRetracted` | Anket, oy veren |

```php
// Olayları dinle
Event::listen(VoteCast::class, function ($event) {
    // $event->poll, $event->voter, $event->votes
});
```

---

## Gelişmiş Özellikler

Aşağıdaki özelliklerin tümü `config/poller.php` üzerinden **isteğe bağlıdır**. Varsayılan ayarlar mevcut davranışı korur.

### Sonuç Önbellekleme

Anket sonuçlarını her istekte yeniden hesaplamamak için önbelleğe alır. Oy verildiğinde, değiştirildiğinde veya geri çekildiğinde önbellek otomatik olarak temizlenir.

```php
// config/poller.php
'cache' => [
    'enabled' => true,
    'store' => null,         // null = varsayılan cache store
    'ttl' => 60,             // saniye
    'prefix' => 'poller',
],
```

```php
$poll->getResultsAsPercentages();   // ilk çağrı DB'ye gider, sonrakiler önbellekten gelir
$poll->flushResultsCache();         // manuel temizleme
```

### Broadcasting

Anket/oy olaylarını Laravel Echo / WebSocket üzerinden yayınla. Kanal adı: `{prefix}.{pollId}`.

```php
// config/poller.php
'broadcasting' => [
    'enabled' => true,
    'channel' => 'private',          // private | presence | public
    'channel_prefix' => 'poller.poll',
],
```

```js
// resources/js — frontend tarafında dinle
Echo.private(`poller.poll.${pollId}`)
    .listen('VoteCast', (e) => updateChart(e.poll));
```

### Oy Veren Hız Sınırı

Tek bir kullanıcının kayan zaman penceresinde tüm anketlere kaç oy verebileceğini sınırlar. Aşıldığında `VoterRateLimitException` fırlatır.

```php
// config/poller.php
'voter_rate_limit' => [
    'enabled' => true,
    'max_votes' => 30,
    'per_minutes' => 60,
],
```

### Çevrilebilir İçerik

Anket/seçenek `title` ve `description` alanlarını JSON dil haritası olarak saklar. `app()->getLocale()` değerini otomatik olarak döndürür, eksikse `fallback_locale`'a düşer.

```php
// config/poller.php
'translatable' => [
    'enabled' => true,
    'fallback_locale' => 'en',
],
```

```php
// Çevirilerle oluştur
Poller::create([
    'title' => ['en' => 'Best framework?', 'tr' => 'En iyi framework?', 'az' => 'Ən yaxşı framework?'],
], $user);

// Geçerli dilde oku
app()->setLocale('tr');
$poll->title;                          // "En iyi framework?"

// Çeviri yardımcıları
$poll->translate('title', 'az');       // "Ən yaxşı framework?"
$poll->setTranslation('title', 'tr', 'Yeni başlık')->save();
$poll->getTranslations('title');       // ['en' => '...', 'tr' => '...', 'az' => '...']
```

### Sorgu Scope'ları

Zincirlenebilir scope'larla anketleri ara ve filtrele:

```php
use Aftandilmmd\Poller\Models\Poll;
use Aftandilmmd\Poller\Enums\PollStatus;
use Aftandilmmd\Poller\Enums\PollType;

Poll::query()
    ->search('framework')                       // başlık veya açıklamada eşleşme
    ->ofStatus(PollStatus::Active)              // enum veya string
    ->ofType(PollType::SingleChoice)
    ->createdBy($user->id)
    ->withinDateRange(now()->subMonth(), now())
    ->get();
```

---

## Hata Yönetimi

Tüm oylama hataları tipli istisnalar fırlatır:

```php
use Aftandilmmd\Poller\Exceptions\PollClosedException;
use Aftandilmmd\Poller\Exceptions\AlreadyVotedException;
use Aftandilmmd\Poller\Exceptions\InvalidSelectionException;
use Aftandilmmd\Poller\Exceptions\UnauthorizedVoteException;
use Aftandilmmd\Poller\Exceptions\CustomOptionException;

try {
    Poller::castVote($poll, $user, $optionId);
} catch (PollClosedException $e) {
    // Anket oy kabul etmiyor
} catch (AlreadyVotedException $e) {
    // Kullanıcı zaten oy vermiş (ve oy değiştirme devre dışı)
} catch (InvalidSelectionException $e) {
    // Yanlış sayıda seçim veya geçersiz seçenek
} catch (UnauthorizedVoteException $e) {
    // Kullanıcının canVote() metodu false döndürdü
} catch (CustomOptionException $e) {
    // Özel seçeneklere izin verilmiyor, limite ulaşıldı veya yetkisiz
}
```

---

## Enum'lar

```php
use Aftandilmmd\Poller\Enums\PollType;
use Aftandilmmd\Poller\Enums\PollStatus;

PollType::SingleChoice->value;   // "single_choice"
PollType::SingleChoice->label(); // "Single Choice"
PollType::SingleChoice->color(); // "green"
PollType::options();             // ["yes_no" => "Yes/No", ...]
PollType::enabled();             // Sadece yapılandırmada etkin türler

PollStatus::Active->value;      // "active"
PollStatus::Active->label();    // "Active"
PollStatus::Active->color();    // "green"
```

---

## Genişletme

### Özel Modeller

Yapılandırmada model sınıflarını geçersiz kılın:

```php
'models' => [
    'poll' => App\Models\CustomPoll::class,
    'option' => App\Models\CustomPollOption::class,
    'vote' => App\Models\CustomPoller::class,
],
```

### Özel Olaylar

Olay sınıflarını değiştirin veya devre dışı bırakın:

```php
'events' => [
    'vote_cast' => App\Events\CustomVoteCast::class,
    'poll_created' => null, // Devre dışı
],
```

---

## Çeviriler

Paket İngilizce, Türkçe ve Azerbaycanca çeviriler içerir. Özelleştirmek veya yeni dil eklemek için:

```bash
php artisan vendor:publish --tag=poller-translations
```

Çeviri dosyaları `lang/vendor/poller/` dizinine yayınlanır.

---

## Test

```bash
composer install
vendor/bin/pest
```

---

## Lisans

MIT
