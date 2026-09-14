#!/usr/bin/env python3
"""
Bouwt platforms.json: per NS-stationscode de perronvlakken (MultiPolygon, WGS84)
uit de open data van ProRail (laag "Perron" in de Transfer-service).

    python3 database/data/build_platforms_json.py > database/data/platforms.json
    php artisan stations:import-platforms

Elk perronvlak gaat naar het dichtstbijzijnde station uit stations.csv, mits de
rand binnen MAX_DISTANCE_M van het stationspunt ligt. Coördinaten worden
afgerond op 6 decimalen (~10 cm).
"""
import csv
import json
import math
import os
import sys
import urllib.parse
import urllib.request

SERVICE = "https://mapservices.prorail.nl/arcgis/rest/services/Transfer_002/FeatureServer/7/query"
MAX_DISTANCE_M = 250
# Stations left without platforms get a second, wider look (Vught: platforms
# ~400 m from the NS point while the station is being rebuilt).
FALLBACK_DISTANCE_M = 600
PAGE = 1000

HERE = os.path.dirname(os.path.abspath(__file__))


def fetch_platforms():
    features = []
    offset = 0
    while True:
        params = urllib.parse.urlencode({
            "where": "1=1",
            "outFields": "OBJECTID",
            "outSR": "4326",
            "resultOffset": offset,
            "resultRecordCount": PAGE,
            "f": "geojson",
        })
        with urllib.request.urlopen(f"{SERVICE}?{params}", timeout=120) as response:
            page = json.load(response)["features"]
        features += page
        if len(page) < PAGE:
            return features
        offset += PAGE


def load_stations():
    with open(os.path.join(HERE, "stations.csv"), newline="", encoding="utf-8") as handle:
        return [
            (row["code"], float(row["longitude"]), float(row["latitude"]))
            for row in csv.DictReader(handle)
            if row["code"] and row["active"] != "0"
        ]


def rings(geometry):
    if geometry["type"] == "Polygon":
        return geometry["coordinates"]
    return [ring for polygon in geometry["coordinates"] for ring in polygon]


def segment_distance(point, a, b):
    """Point-to-segment distance in meters (local equirectangular approximation)."""
    kx = math.cos(math.radians(point[1])) * 111320
    ky = 110540
    px, py = (point[0] - a[0]) * kx, (point[1] - a[1]) * ky
    bx, by = (b[0] - a[0]) * kx, (b[1] - a[1]) * ky
    length = bx * bx + by * by
    t = 0 if length == 0 else max(0.0, min(1.0, (px * bx + py * by) / length))
    return math.hypot(px - t * bx, py - t * by)


def edge_distance(point, geometry):
    best = math.inf
    for ring in rings(geometry):
        for i in range(len(ring) - 1):
            best = min(best, segment_distance(point, ring[i], ring[i + 1]))
    return best


def polygons(geometry):
    if geometry["type"] == "Polygon":
        return [geometry["coordinates"]]
    return geometry["coordinates"]


def main():
    stations = load_stations()
    features = fetch_platforms()
    print(f"{len(features)} perronvlakken, {len(stations)} stations", file=sys.stderr)

    def nearest_station(geometry, candidates):
        first = rings(geometry)[0][0]
        best_code, best_distance = None, math.inf
        for code, lon, lat in candidates:
            # Cheap pre-filter on the first vertex before the exact edge distance.
            if abs(lon - first[0]) > 0.02 or abs(lat - first[1]) > 0.012:
                continue
            distance = edge_distance((lon, lat), geometry)
            if distance < best_distance:
                best_code, best_distance = code, distance
        return best_code, best_distance

    def assign(code, geometry):
        result.setdefault(code, []).extend(
            [[[round(x, 6), round(y, 6)] for x, y in ring] for ring in polygon]
            for polygon in polygons(geometry)
        )

    result = {}
    leftover = []
    for feature in features:
        code, distance = nearest_station(feature["geometry"], stations)
        if code is None or distance > MAX_DISTANCE_M:
            leftover.append(feature)
        else:
            assign(code, feature["geometry"])

    without = [station for station in stations if station[0] not in result]
    for feature in leftover:
        code, distance = nearest_station(feature["geometry"], without)
        if code is not None and distance <= FALLBACK_DISTANCE_M:
            assign(code, feature["geometry"])
            print(f"{code}: perron op {distance:.0f} m via ruimere zoekafstand", file=sys.stderr)

    missing = [station[0] for station in stations if station[0] not in result]
    print(f"{len(result)} stations met perrons, {len(leftover)} vlakken buiten {MAX_DISTANCE_M} m, zonder perrons: {missing or 'geen'}", file=sys.stderr)
    json.dump(
        {code: {"type": "MultiPolygon", "coordinates": result[code]} for code in sorted(result)},
        sys.stdout,
        separators=(",", ":"),
    )
    print()


if __name__ == "__main__":
    main()
