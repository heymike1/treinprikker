# Treinprikker 🚆

**Hoe goed ken jij het Nederlandse spoor?**

Elke dag vijf Nederlandse treinstations. Prik ze op de kaart en ontdek hoe goed jij het Nederlandse spoor kent.
Iedereen krijgt dezelfde vijf stations, maximaal 1000 punten per station, 5000 per dag. Deel je resultaat, bouw een streak
en ontdek samen met alle spelers welk station het moeilijkst te vinden is.

## Stack

Laravel 12 · PHP 8.3 · MySQL · Livewire 3 · Alpine.js · Tailwind CSS 4 · Vite 6 · MapLibre GL JS (OpenFreeMap / OpenStreetMap-tiles)

## Installatie

```bash
composer install
cp .env.example .env            # vul DB_* in
php artisan key:generate
php artisan migrate
php artisan db:seed             # importeert stations.csv en genereert de komende Treinprikkers
nvm use                         # Node 22 (.nvmrc)
npm install && npm run build
php artisan serve
```

Scheduler (elke minuut) op de server:

```
* * * * * php /pad/naar/artisan schedule:run >> /dev/null 2>&1
```

Geplande taken (Europe/Amsterdam):

| Taak | Wanneer |
| --- | --- |
| `treinprikker:generate-daily` | elk uur; houdt 7 dagen vooruit klaar en werkt statussen bij |
| `treinprikker:recalculate-stats` | elk uur (xx:15) |

Ontbreekt de Treinprikker van vandaag toch (scheduler nooit gedraaid), dan wordt hij bij het eerste bezoek achter een lock gegenereerd.

## Artisan-commando's

| Commando | Doel |
| --- | --- |
| `stations:import [pad] [--deactivate-missing] [--reset-difficulty]` | Stations importeren/bijwerken uit `database/data/stations.csv` (zie `database/data/README.md`) |
| `treinprikker:generate-daily [--days=7] [--date=YYYY-MM-DD] [--force]` | Daily Games genereren; `--date` + `--force` kiest een dag opnieuw |
| `treinprikker:recalculate-stats` | Stationsstatistieken, moeilijkheid en dagstatistieken herberekenen uit de ruwe prikken |
| `treinprikker:admin email@voorbeeld.nl [--password=...]` | Beheerder aanmaken voor `/admin` (HTTP basic auth) |
| `treinprikker:reset [--force]` | Alle potjes, prikken, spelers en statistieken wissen; stations, dagen en beheerders blijven |

## Hoe het spel werkt

- Eén `DailyGame` per Nederlandse kalenderdag (`Europe/Amsterdam`, nooit UTC), met vijf `DailyGameStation`-rondes.
- Een browser krijgt een willekeurige UUID in een versleutelde cookie (`players.anonymous_id`); geen fingerprinting, geen account.
  `players.user_id` is voorbereid zodat een toekomstig account een anonieme speler kan claimen.
- Eén `GameSession` per speler per dag (unieke index), dus verversen maakt nooit een dubbel potje en het spel gaat verder waar je was.
- Elke individuele `Guess` wordt permanent bewaard (inclusief een snapshot van de stationscoördinaten) en is de bron voor alle statistieken.

### Niveaus

Vóór station 1 kiest de speler een niveau (`config/treinprikker.php` → `modes`); het niveau staat op `game_sessions.mode` en ligt daarna vast.

| Niveau | Kaart | Vraag | Tijd |
| --- | --- | --- | --- |
| Makkelijk | luchtfoto | stationsnaam | onbeperkt |
| Moeilijk | luchtfoto | stationsnaam | 20 s per station |
| Expert | kale kaart (land, water, grenzen) | NS-code + stationstype als hint | 20 s per station |

De tijd wordt server-side bewaakt via `game_sessions.round_started_at` (plus `time_limit_grace_seconds`): een te late prik telt als
`timed_out` met 0 punten en zonder coördinaten. Zonder pin bij het aflopen wordt de ronde als timed out vastgelegd en het station
getoond. Timed-out rondes tellen niet mee als prik in de statistieken. Alle niveaus scoren gelijk; "beter dan X%" vergelijkt alleen
binnen hetzelfde niveau en de deeltekst vermeldt het niveau (behalve Makkelijk).

### Anti-cheat

De Livewire-component (`App\Livewire\PlayGame`) stuurt alleen de naam van het huidige station naar de browser.
Coördinaten van een station komen pas terug in de response van `submitGuess`. De echte locatie wordt alleen in
`App\Game\GameService::submitGuess()` gelezen; afstand en score worden server-side berekend.

### Score

`App\Game\ScoreCalculator`, configureerbaar in `config/treinprikker.php`:

```
score = 1000 · exp( −(afstand_km / λ)^1,2 )      λ = 50 / ln(2)^(1/1,2) ≈ 67,9 km
```

| Afstand | 0 km | 1 km | 5 km | 10 km | 25 km | 50 km | 100 km | 300 km |
| --- | --- | --- | --- | --- | --- | --- | --- | --- |
| Punten | 1000 | 994 | 957 | 904 | 740 | 500 | 204 | 3 |

Afstanden komen uit `App\Support\DistanceCalculator` (Haversine) en worden in meters opgeslagen.

### Moeilijkheid en stationskeuze

Elk station heeft een `difficulty_rating` van 1 (heel makkelijk) tot 100 (heel moeilijk).

- Startwaarde: heuristiek op basis van stationstype en naam (`App\Game\StationDifficultyHeuristic`).
- Vanaf `difficulty.minimum_guesses` prikken vervangt `App\Statistics\StationDifficultyCalculator` de heuristiek door data:
  `1 + 99 · (0,4·mediaan/60km + 0,3·(1 − gem.score/1000) + 0,2·(1 − %binnen10km) + 0,1·spreiding/60km)`.
- `App\Game\DailyGameGenerator` kiest per dag `easy, medium, medium, hard, wildcard`, vermijdt stations die binnen
  `station_repeat_cooldown_days` (voor én na de datum) al gebruikt zijn en pakt liefst vijf verschillende gemeenten.

### Statistieken

Na elk uitgespeeld potje herberekent een queue-job (`RefreshStatisticsForSession`) de statistieken van de vijf gespeelde stations en van die dag en leegt de cache, zodat `/statistieken` direct meebeweegt. `treinprikker:recalculate-stats` doet elk uur de volledige ronde en bouwt uit de ruwe prikken:

- `station_statistics`: aantal, gemiddelde/mediaan/25e/75e percentiel afstand, gemiddelde/mediaan score, % binnen 1/5/10/25/50 km,
  centroïde van alle prikken + afstand en kompasrichting t.o.v. het station ("spelers plaatsen X gemiddeld 14 km te noordelijk"),
  spreiding (interkwartielafstand), datagedreven moeilijkheidsscore en -rang.
- `daily_game_statistics`: spelers, voltooid, gemiddelde/mediaan score, hoogste/laagste score per dag.

Medianen en percentielen komen via `ORDER BY … OFFSET` rechtstreeks uit de database (`App\Statistics\Percentiles`), zonder alle prikken in PHP te laden.
Globale cijfers (`/statistieken`) worden `statistics.cache_ttl` seconden gecachet en na herberekening geleegd.
Stations en dagen worden pas gerangschikt na `minimum_station_guesses` resp. `minimum_daily_game_completions`; daarvoor toont de pagina dat de statistieken nog opgebouwd worden.

### Kaart

De **speelkaart** toont luchtfoto's (Esri World Imagery, `map.satellite` in de config) met daarop alleen lands- en provinciegrenzen:
geen wegen, geen plaats- of straatnamen, geen stations-POI's. Je oriënteert op kust, water, steden en het spoor dat je in de foto ziet.

De **statistiekenkaarten** gebruiken de OpenFreeMap "positron"-vectorstijl (`TREINPRIKKER_MAP_STYLE_URL`); `railway*`- en `airport`-lagen worden
client-side verwijderd en labels gebruiken `name:nl`. Wil je zelf tiles hosten, wijs de env-variabele naar je eigen style-JSON.

## Routes

| Route | Wat |
| --- | --- |
| `/` | De Treinprikker van vandaag |
| `/statistieken` | Globale statistieken + kaart van alle stations op moeilijkheid |
| `/statistieken/stations.json` | GeoJSON van alle actieve stations (voor de kaart) |
| `/mijn-statistieken` | Persoonlijke statistieken en streaks |
| `/station/{slug}` | Statistieken per station |
| `/hoe-werkt-het` | Uitleg |
| `/admin`, `/admin/dagen`, `/admin/stations`, `/admin/statistieken` | Beheer (basic auth, `is_admin`) |

## Analytics

Eerste-partij events (`analytics_events`): `game_started`, `guess_submitted`, `game_completed`, `share_clicked`, `stats_viewed`.
Geen trackers van derden.

## Tests en code-stijl

```bash
php artisan test
vendor/bin/pint
```
# treinprikker
