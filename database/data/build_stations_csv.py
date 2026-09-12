#!/usr/bin/env python3
"""
Builds database/data/stations.csv from two open datasets:

  1. Rijden de Treinen station list (CC BY 4.0)
     https://www.rijdendetreinen.nl/open-data/treinstations
     -> stations-YYYY-MM-nl.csv  (code, uic, names, slug, type, lat/lng)

  2. CBS / Kadaster generalised province + municipality boundaries via cartomap
     https://cartomap.github.io/nl/wgs84/provincie_2025.geojson
     https://cartomap.github.io/nl/wgs84/gemeente_2025.geojson

Province and municipality are derived by point-in-polygon; nothing is invented.

Usage:
  python3 build_stations_csv.py stations-2023-09-nl.csv provincie.geojson gemeente.geojson > stations.csv
"""
import csv
import json
import sys


def point_in_ring(lng, lat, ring):
    inside = False
    n = len(ring)
    j = n - 1
    for i in range(n):
        xi, yi = ring[i][0], ring[i][1]
        xj, yj = ring[j][0], ring[j][1]
        if (yi > lat) != (yj > lat):
            x_cross = (xj - xi) * (lat - yi) / (yj - yi) + xi
            if lng < x_cross:
                inside = not inside
        j = i
    return inside


def point_in_polygon(lng, lat, polygon):
    if not point_in_ring(lng, lat, polygon[0]):
        return False
    for hole in polygon[1:]:
        if point_in_ring(lng, lat, hole):
            return False
    return True


def point_in_feature(lng, lat, feature):
    geom = feature['geometry']
    if geom['type'] == 'Polygon':
        return point_in_polygon(lng, lat, geom['coordinates'])
    if geom['type'] == 'MultiPolygon':
        return any(point_in_polygon(lng, lat, p) for p in geom['coordinates'])
    return False


def nearest_feature(lng, lat, features):
    """Fallback for stations sitting on generalised boundary edges: nearest vertex."""
    best, best_d = None, None
    for f in features:
        geom = f['geometry']
        polys = geom['coordinates'] if geom['type'] == 'MultiPolygon' else [geom['coordinates']]
        for poly in polys:
            for x, y in poly[0]:
                d = (x - lng) ** 2 + (y - lat) ** 2
                if best_d is None or d < best_d:
                    best, best_d = f, d
    return best


def lookup(lng, lat, features):
    for f in features:
        if point_in_feature(lng, lat, f):
            return f['properties']['statnaam']
    f = nearest_feature(lng, lat, features)
    return f['properties']['statnaam'] if f else ''


def main():
    src, prov_path, gem_path = sys.argv[1:4]
    provinces = json.load(open(prov_path))['features']
    municipalities = json.load(open(gem_path))['features']

    # Event-only stations are not regular passenger stations.
    inactive_types = {'facultatiefStation'}

    out = csv.writer(sys.stdout, lineterminator='\n')
    out.writerow(['code', 'uic', 'name', 'slug', 'latitude', 'longitude', 'province', 'municipality', 'type', 'active'])

    rows = list(csv.DictReader(open(src, encoding='utf-8')))
    rows.sort(key=lambda r: r['name_long'].lower())
    for r in rows:
        if r['country'] != 'NL':
            continue
        lat, lng = float(r['geo_lat']), float(r['geo_lng'])
        out.writerow([
            r['code'],
            r['uic'],
            r['name_long'],
            r['slug'],
            f"{lat:.6f}",
            f"{lng:.6f}",
            lookup(lng, lat, provinces),
            lookup(lng, lat, municipalities),
            r['type'],
            0 if r['type'] in inactive_types else 1,
        ])


if __name__ == '__main__':
    main()
