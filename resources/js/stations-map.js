import maplibregl from 'maplibre-gl';
import { loadCleanStyle, createMap } from './map-style';

const COLORS = { easy: '#2f8f5b', medium: '#d08a00', hard: '#c9432f' };

/**
 * Netherlands map with every station coloured by difficulty. Clicking a
 * station opens its statistics page. Optionally highlights one station and
 * draws where players collectively placed it (config.highlight / config.centroid).
 */
export default function stationsMap(config) {
    // Kept out of Alpine's reactive data: a proxied MapLibre map is very slow.
    let map = null;

    return {
        failed: false,
        active: null,

        async init() {
            try {
                const [style, response] = await Promise.all([loadCleanStyle(config.styleUrl), fetch(config.dataUrl)]);
                const data = await response.json();
                map = createMap(this.$refs.map, style, config);

                map.on('load', () => {
                    map.fitBounds(config.bounds, { padding: 16, duration: 0 });
                    map.addSource('stations', { type: 'geojson', data });
                    map.addLayer({
                        id: 'stations',
                        type: 'circle',
                        source: 'stations',
                        paint: {
                            'circle-radius': ['interpolate', ['linear'], ['zoom'], 5, 3, 9, 7, 12, 10],
                            'circle-color': ['match', ['get', 'bucket'], 'easy', COLORS.easy, 'hard', COLORS.hard, COLORS.medium],
                            'circle-stroke-color': '#ffffff',
                            'circle-stroke-width': 1.5,
                            'circle-opacity': 0.9,
                        },
                    });

                    if (config.highlight) {
                        map.addLayer({
                            id: 'stations-highlight',
                            type: 'circle',
                            source: 'stations',
                            filter: ['==', ['get', 'slug'], config.highlight],
                            paint: { 'circle-radius': 12, 'circle-color': '#1d3f8f', 'circle-stroke-color': '#ffffff', 'circle-stroke-width': 3 },
                        });
                    }

                    if (config.centroid && config.station) {
                        map.addSource('centroid-line', {
                            type: 'geojson',
                            data: { type: 'Feature', geometry: { type: 'LineString', coordinates: [config.station, config.centroid] } },
                        });
                        map.addLayer({
                            id: 'centroid-line',
                            type: 'line',
                            source: 'centroid-line',
                            paint: { 'line-color': '#1d3f8f', 'line-width': 2.5, 'line-dasharray': [1.5, 1.5] },
                        });
                        const el = document.createElement('div');
                        el.className = 'map-pin map-pin-guess';
                        el.setAttribute('role', 'img');
                        el.setAttribute('aria-label', 'Gemiddelde prik van spelers');
                        new maplibregl.Marker({ element: el, anchor: 'bottom' }).setLngLat(config.centroid).addTo(map);
                    }

                    const popup = new maplibregl.Popup({ closeButton: false, closeOnClick: false, offset: 10 });

                    map.on('mouseenter', 'stations', (event) => {
                        map.getCanvas().style.cursor = 'pointer';
                        const feature = event.features[0];
                        popup.setLngLat(feature.geometry.coordinates).setText(feature.properties.name).addTo(map);
                    });
                    map.on('mouseleave', 'stations', () => {
                        map.getCanvas().style.cursor = '';
                        popup.remove();
                    });
                    map.on('click', 'stations', (event) => {
                        const feature = event.features[0];
                        this.active = feature.properties;
                    });
                    map.on('click', (event) => {
                        const hits = map.queryRenderedFeatures(event.point, { layers: ['stations'] });
                        if (hits.length === 0) {
                            this.active = null;
                        }
                    });
                });
            } catch (error) {
                console.error(error);
                this.failed = true;
            }
        },
    };
}
