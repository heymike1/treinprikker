#!/usr/bin/env python3
"""
Bouwt buildings.json: per NS-stationscode de stationsgebouwen (MultiPolygon,
WGS84) uit de BAG (Kadaster, via de PDOK WFS, CC0).

    python3 database/data/build_buildings_json.py > database/data/buildings.json
    php artisan stations:import-platforms   # laadt perrons én gebouwen

Een pand telt als stationsgebouw als het het stationspunt bevat of binnen
ADJACENT_M van een perronvlak uit platforms.json ligt, groter is dan een schuur
of rijtjeshuis, en nergens verder dan MAX_REACH_M van de perrons reikt (zo
vallen winkelcentra als Alexandrium en Hoog Catharijne af). Fietsenstallingen
tegen het perron aan tellen dus mee; dat is prima voor een raak-zone.
"""
import csv
import json
import math
import os
import sys
import urllib.parse
import urllib.request

WFS = "https://service.pdok.nl/lv/bag/wfs/v2_0"
ADJACENT_M = 20
MIN_AREA_M2 = 200
MAX_AREA_M2 = 40000
MAX_REACH_M = 250
MARGIN_DEG = 0.0006  # ~40-65 m around the platforms

HERE = os.path.dirname(os.path.abspath(__file__))


def load_stations():
    with open(os.path.join(HERE, "stations.csv"), newline="", encoding="utf-8") as handle:
        return [
            (row["code"], float(row["latitude"]), float(row["longitude"]))
            for row in csv.DictReader(handle)
            if row["code"] and row["active"] != "0"
        ]


def load_platforms():
    with open(os.path.join(HERE, "platforms.json"), encoding="utf-8") as handle:
        return {code: geometry["coordinates"] for code, geometry in json.load(handle).items()}


def fetch_panden(min_lat, min_lon, max_lat, max_lon):
    params = urllib.parse.urlencode({
        "service": "WFS", "version": "2.0.0", "request": "GetFeature", "typeName": "bag:pand",
        "outputFormat": "json", "srsName": "EPSG:4326", "count": 500,
        "bbox": f"{min_lat},{min_lon},{max_lat},{max_lon},EPSG:4326",
    })
    with urllib.request.urlopen(f"{WFS}?{params}", timeout=60) as response:
        return json.load(response)["features"]


def polygons(geometry):
    return [geometry["coordinates"]] if geometry["type"] == "Polygon" else geometry["coordinates"]


def area_m2(ring):
    kx = math.cos(math.radians(ring[0][1])) * 111320
    ky = 110540
    total = 0.0
    for i in range(len(ring) - 1):
        total += (ring[i][0] * kx) * (ring[i + 1][1] * ky) - (ring[i + 1][0] * kx) * (ring[i][1] * ky)
    return abs(total) / 2


def contains(ring, point):
    inside = False
    j = len(ring) - 1
    for i in range(len(ring)):
        xi, yi = ring[i]
        xj, yj = ring[j]
        if (yi > point[1]) != (yj > point[1]) and point[0] < (xj - xi) * (point[1] - yi) / (yj - yi) + xi:
            inside = not inside
        j = i
    return inside


def segment_distance(point, a, b):
    kx = math.cos(math.radians(point[1])) * 111320
    ky = 110540
    px, py = (point[0] - a[0]) * kx, (point[1] - a[1]) * ky
    bx, by = (b[0] - a[0]) * kx, (b[1] - a[1]) * ky
    length = bx * bx + by * by
    t = 0 if length == 0 else max(0.0, min(1.0, (px * bx + py * by) / length))
    return math.hypot(px - t * bx, py - t * by)


def ring_distance(ring_a, ring_b):
    """Smallest distance between two rings' vertices and edges (0 when they overlap)."""
    best = math.inf
    for point in ring_a:
        if contains(ring_b, point):
            return 0.0
        for i in range(len(ring_b) - 1):
            best = min(best, segment_distance(point, ring_b[i], ring_b[i + 1]))
    for point in ring_b:
        if contains(ring_a, point):
            return 0.0
        for i in range(len(ring_a) - 1):
            best = min(best, segment_distance(point, ring_a[i], ring_a[i + 1]))
    return best


def reach(ring, platform_rings):
    """How far the farthest vertex of a building is from the nearest platform edge."""
    worst = 0.0
    for point in ring:
        best = math.inf
        for platform in platform_rings:
            for i in range(len(platform) - 1):
                best = min(best, segment_distance(point, platform[i], platform[i + 1]))
        worst = max(worst, best)
    return worst


def main():
    platforms = load_platforms()
    result = {}

    for code, lat, lon in load_stations():
        rings = [ring for polygon in platforms.get(code, []) for ring in polygon[:1]]
        lats = [lat] + [p[1] for ring in rings for p in ring]
        lons = [lon] + [p[0] for ring in rings for p in ring]
        try:
            panden = fetch_panden(min(lats) - MARGIN_DEG, min(lons) - MARGIN_DEG, max(lats) + MARGIN_DEG, max(lons) + MARGIN_DEG)
        except Exception as error:  # noqa: BLE001
            print(f"{code}: BAG niet bereikbaar ({error})", file=sys.stderr)
            continue

        for pand in panden:
            for polygon in polygons(pand["geometry"]):
                outer = polygon[0]
                size = area_m2(outer)
                if size < MIN_AREA_M2 or size > MAX_AREA_M2:
                    continue
                if not (contains(outer, (lon, lat)) or any(ring_distance(outer, ring) <= ADJACENT_M for ring in rings)):
                    continue
                if rings and reach(outer, rings) > MAX_REACH_M:
                    continue
                result.setdefault(code, []).append([[[round(x, 6), round(y, 6)] for x, y in ring] for ring in polygon])

        print(f"{code}: {len(result.get(code, []))} gebouw(en)", file=sys.stderr)

    print(f"{len(result)} stations met gebouw", file=sys.stderr)
    json.dump(
        {code: {"type": "MultiPolygon", "coordinates": result[code]} for code in sorted(result)},
        sys.stdout,
        separators=(",", ":"),
    )
    print()


if __name__ == "__main__":
    main()
