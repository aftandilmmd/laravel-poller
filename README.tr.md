[English](README.md) | **Türkçe** | [Azərbaycanca](README.az.md)

# Laravel Poll Vote

Laravel için güçlü ve esnek bir anket ve oylama paketi. 5 anket türünü, anonim oylamayı, zamanlanmış anketleri, oy değiştirmeyi ve hem Livewire bileşenleri hem de RESTful API destekler.

## Gereksinimler

- PHP 8.2+
- Laravel 11 veya 12

## Kurulum

```bash
composer require aftandilmmd/laravel-poll-vote
```

Servis sağlayıcı ve facade otomatik olarak keşfedilir.

Yapılandırma dosyasını yayınlayın:

```bash
php artisan vendor:publish --tag=poll-vote-config
```

Migration dosyalarını yayınlayın (isteğe bağlı - migration'lar otomatik çalışır):

```bash
php artisan vendor:publish --tag=poll-vote-migrations
```

Görünümleri yayınlayın (isteğe bağlı - özelleştirme için):

```bash
php artisan vendor:publish --tag=poll-vote-views
```

Çevirileri yayınlayın (isteğe bağlı - özelleştirme için):

```bash
php artisan vendor:publish --tag=poll-vote-translations
```

Migration'ları çalıştırın:

```bash
php artisan migrate
```

## Yapılandırma

Tüm yapılandırma seçenekleri `config/poll-vote.php` dosyasında:

| Anahtar | Açıklama | Varsayılan |
|---------|----------|------------|
| `user_model` | Kullanıcı model sınıfı | `App\Models\User` |
| `tables.polls` | Anketler tablo adı | `poll_vote_polls` |
| `tables.options` | Seçenekler tablo adı | `poll_vote_poll_options` |
| `tables.votes` | Oylar tablo adı | `poll_vote_poll_votes` |
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
use Aftandilmmd\PollVote\Traits\HasPolls;

class Meeting extends Model
{
    use HasPolls;
}
```

### Kullanıcı modeline oylama yeteneği ekleyin

```php
use Aftandilmmd\PollVote\Traits\InteractsWithPolls;

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
use Aftandilmmd\PollVote\Facades\PollVote;

// Anket oluştur
$poll = PollVote::create([
    'title' => 'En iyi framework?',
    'type' => 'single_choice',
    'is_anonymous' => false,
    'show_results_before_close' => true,
    'allow_vote_change' => true,
], $user);

// Seçenek ekle
PollVote::addOption($poll, ['title' => 'Laravel']);
PollVote::addOption($poll, ['title' => 'Django']);
PollVote::addOption($poll, ['title' => 'Rails']);

// Anketi etkinleştir
PollVote::activate($poll);

// Oy ver
PollVote::castVote($poll, $user, $optionId);

// Yorumla oy ver
PollVote::castVote($poll, $user, $optionId, ['comment' => 'Harika seçim!']);

// Oy değiştir
PollVote::changeVote($poll, $user, $newOptionId);

// Oy geri çek
PollVote::retractVote($poll, $user);

// Sonuçları al
$results = PollVote::getResults($poll);
// [['option_id' => 1, 'title' => 'Laravel', 'votes_count' => 15, 'percentage' => 75.0], ...]

$detailed = PollVote::getDetailedResults($poll);
// ['poll' => ..., 'total_votes' => 20, 'unique_voters' => 18, 'options' => [...], 'leading_option' => ...]

// Yaşam döngüsü
PollVote::close($poll);
PollVote::cancel($poll);

// Seçenekleri yeniden sırala
PollVote::reorderOptions($poll, [$optionId3, $optionId1, $optionId2]);

// Anketi kopyala (tüm seçenekleriyle)
$newPoll = PollVote::duplicate($poll, ['title' => 'Anketin kopyası']);
```

### Özel Seçenekler

Oy verenlerin ankete kendi seçeneklerini eklemesine izin verin. Maksimum sayıyı ve kimlerin ekleyebileceğini kontrol edin.

```php
// Özel seçenekler etkin bir anket oluşturun (maks 5)
$poll = PollVote::create([
    'title' => 'En iyi framework?',
    'type' => 'single_choice',
    'allow_custom_options' => true,
    'max_custom_options' => 5, // null = sınırsız
], $user);

// Özel seçenek ekle (Facade ile)
PollVote::addCustomOption($poll, $user, ['title' => 'Benim önerim']);

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

Livewire `PollVote` widget'ı, özel seçenekler etkinleştirildiğinde ve kullanıcı yetkili olduğunda otomatik olarak "Kendi seçeneğinizi ekleyin" giriş alanını gösterir.

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
<livewire:poll-vote-poll-manager />

{{-- Belirli bir modele kapsamlı --}}
<livewire:poll-vote-poll-manager :pollable="$meeting" />
```

Özellikler: Arama, duruma/türe göre filtreleme, oluşturma, düzenleme, silme, etkinleştirme, kapatma, anket kopyalama.

### Anket Formu (Oluştur/Düzenle)

```blade
<livewire:poll-vote-poll-form />
<livewire:poll-vote-poll-form :poll-id="$poll->id" />
```

### Anket Görünümü (Tam Görüntüleme)

```blade
<livewire:poll-vote-poll-display :poll="$poll" />
```

Anket bilgilerini, istatistikleri, oylama arayüzünü, sonuçları ve oy geçmişi sekmelerini gösterir.

### Anket Sonuçları (Analiz)

```blade
<livewire:poll-vote-poll-results :poll="$poll" />
```

Yüzdelikler ve önde gelen seçenek ile çubuk grafik sonuçlarını gösterir.

### Anket Oyu (Kompakt Widget)

```blade
<livewire:poll-vote-poll-vote :poll="$poll" />
```

Gömülür oylama widget'ı. Uygun arayüz ile (radyo, onay kutusu, derecelendirme ölçeği, sıralama) tüm 5 anket türünü destekler.

### Görünümleri Özelleştirme

```bash
php artisan vendor:publish --tag=poll-vote-views
```

Görünümler `resources/views/vendor/poll-vote/` dizinine yayınlanacaktır.

---

## REST API

Yapılandırma dosyasında API'yi etkinleştirin:

```php
// config/poll-vote.php
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
Schedule::command('poll-vote:auto-open')->everyMinute();
Schedule::command('poll-vote:auto-close')->everyMinute();
```

- `poll-vote:auto-open` -- `starts_at` tarihi geçmiş taslak anketleri etkinleştirir
- `poll-vote:auto-close` -- `ends_at` tarihi geçmiş aktif anketleri kapatır

### Bakım Komutları

```bash
# Tüm seçenek oy sayılarını gerçek oy kayıtlarından yeniden hesapla
php artisan poll-vote:reconcile-counts
```

---

## Olaylar

Tüm olaylar `config/poll-vote.php` üzerinden yapılandırılabilir. Devre dışı bırakmak için `null` olarak ayarlayın.

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

## Hata Yönetimi

Tüm oylama hataları tipli istisnalar fırlatır:

```php
use Aftandilmmd\PollVote\Exceptions\PollClosedException;
use Aftandilmmd\PollVote\Exceptions\AlreadyVotedException;
use Aftandilmmd\PollVote\Exceptions\InvalidSelectionException;
use Aftandilmmd\PollVote\Exceptions\UnauthorizedVoteException;
use Aftandilmmd\PollVote\Exceptions\CustomOptionException;

try {
    PollVote::castVote($poll, $user, $optionId);
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
use Aftandilmmd\PollVote\Enums\PollType;
use Aftandilmmd\PollVote\Enums\PollStatus;

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
    'vote' => App\Models\CustomPollVote::class,
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
php artisan vendor:publish --tag=poll-vote-translations
```

Çeviri dosyaları `lang/vendor/poll-vote/` dizinine yayınlanır.

---

## Test

```bash
php artisan test --filter=PollVote
```

---

## Lisans

MIT
