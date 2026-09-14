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

## Spoornet (Expert-kaart)

`public/data/spoornet.json` is het Nederlandse spoornet als één MultiLineString, gebruikt op de kale Expert-kaart.
Bron: OpenStreetMap (ODbL), Overpass-query `way["railway"="rail"]["usage"~"^(main|branch)$"]["service"!~"."]` binnen Nederland,
vereenvoudigd met Douglas-Peucker (~50 m) en afgerond op 4 decimalen. Het bevat alleen lijnen, geen stations.

## Perrons

`platforms.json` bevat per NS-stationscode de perronvlakken als GeoJSON-MultiPolygon (WGS84, 6 decimalen) en is de bron voor
`php artisan stations:import-platforms`. Een prik op een perron telt in het spel als 0 m.

Bron: **ProRail open data**, laag *Perron* uit de Transfer-service (`mapservices.prorail.nl/arcgis/rest/services/Transfer_002`).
Elk perronvlak gaat naar het dichtstbijzijnde station uit `stations.csv` als de rand binnen 250 m van het stationspunt ligt;
stations die zo niets krijgen worden nog eens tot 600 m gezocht (Vught, in verbouwing).

```bash
python3 database/data/build_platforms_json.py > database/data/platforms.json
php artisan stations:import-platforms
php artisan treinprikker:recalculate-distances
```
