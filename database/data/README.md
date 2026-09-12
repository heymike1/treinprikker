# Stationsdata

`stations.csv` is de bron voor `php artisan stations:import`.

Kolommen: `code,uic,name,slug,latitude,longitude,province,municipality,type,active`

- **code** – NS-stationscode (bijv. `ASD`). Primaire, stabiele sleutel voor de importer.
- **uic** – internationale UIC-code. Tweede sleutel als de code ontbreekt.
- **slug** – derde sleutel en de publieke URL (`/station/{slug}`).
- **type** – stationstype uit de bron (`megastation`, `intercitystation`, `stoptreinstation`, ...). Wordt gebruikt voor de heuristische startmoeilijkheid.
- **active** – `0` voor stations die niet in een Treinprikker mogen voorkomen (evenementstations zoals Rotterdam Stadion).

## Bron

Gebouwd met `build_stations_csv.py` uit twee open datasets:

1. **Rijden de Treinen – stationslijst** (CC BY 4.0), `stations-2023-09-nl.csv`
   https://www.rijdendetreinen.nl/open-data/treinstations
   Bevat code, UIC, namen, slug, type en coördinaten van alle Nederlandse treinstations.
2. **CBS/Kadaster gegeneraliseerde provincie- en gemeentegrenzen** via cartomap
   https://cartomap.github.io/nl/wgs84/provincie_2025.geojson
   https://cartomap.github.io/nl/wgs84/gemeente_2025.geojson
   Provincie en gemeente worden per station bepaald met point-in-polygon. Er worden geen coördinaten verzonnen.

## Vernieuwen

```bash
python3 database/data/build_stations_csv.py stations-YYYY-MM-nl.csv provincie.geojson gemeente.geojson > database/data/stations.csv
php artisan stations:import --deactivate-missing
```

De importer werkt bestaande stations bij op code/uic/slug, voegt nieuwe toe en laat ID's,
prikken en statistieken intact. Datagedreven moeilijkheidsratings blijven staan;
gebruik `--reset-difficulty` om heuristische ratings opnieuw te berekenen.
