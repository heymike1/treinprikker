import maplibregl from 'maplibre-gl';
import { loadCleanStyle, createMap, pinElement } from './map-style';

/**
 * Alpine component that drives the guessing map. It never knows the answer
 * until the server returns it after a guess.
 *
 * The MapLibre map and markers live in closure variables on purpose: putting
 * them on the Alpine data object would wrap them in a deep reactive proxy,
 * which makes MapLibre painfully slow.
 */
export default function gameMap(config) {
    let map = null;
    let guessMarker = null;
    let actualMarker = null;

    return {
        ready: false,
        failed: false,
        pending: null,
        busy: false,
        locked: false,
        error: null,

        get hasGuess() {
            return this.pending !== null;
        },

        async init() {
            if (map) {
                return;
            }

            try {
                const style = await loadCleanStyle(config.styleUrl);
                map = createMap(this.$refs.map, style, config);
            } catch (error) {
                console.error(error);
                this.failed = true;
                return;
            }

            map.on('load', () => {
                map.fitBounds(config.bounds, { padding: 12, duration: 0 });
                map.addSource('guess-line', { type: 'geojson', data: emptyLine() });
                map.addLayer({
                    id: 'guess-line',
                    type: 'line',
                    source: 'guess-line',
                    paint: { 'line-color': '#1d3f8f', 'line-width': 2.5, 'line-dasharray': [1.5, 1.5] },
                });
                this.ready = true;
            });

            map.on('click', (event) => this.place(event.lngLat));
        },

        place(lngLat) {
            if (this.locked || this.busy || !this.ready) {
                return;
            }

            this.pending = { lat: lngLat.lat, lng: lngLat.lng };

            if (!guessMarker) {
                guessMarker = new maplibregl.Marker({ element: pinElement('guess'), anchor: 'bottom', draggable: true })
                    .setLngLat(lngLat)
                    .addTo(map);
                guessMarker.on('dragend', () => {
                    const position = guessMarker.getLngLat();
                    this.pending = { lat: position.lat, lng: position.lng };
                });
            } else {
                guessMarker.setLngLat(lngLat);
            }
        },

        async submit() {
            if (!this.hasGuess || this.busy || this.locked) {
                return;
            }

            this.busy = true;
            this.error = null;
            try {
                const result = await this.$wire.submitGuess(this.pending.lat, this.pending.lng);
                if (result && result.ok) {
                    await this.showResult(result);
                } else if (result && result.reload) {
                    window.setTimeout(() => window.location.reload(), 1500);
                }
            } catch (error) {
                console.error(error);
                this.error = 'De prik kon niet worden verstuurd. Controleer je verbinding en probeer het opnieuw.';
            } finally {
                this.busy = false;
            }
        },

        async showResult(result) {
            this.locked = true;
            map.getCanvas().classList.add('cursor-default');
            guessMarker?.setDraggable(false);
            guessMarker?.setLngLat([result.guessed.lng, result.guessed.lat]);

            actualMarker = new maplibregl.Marker({ element: pinElement('station'), anchor: 'center' })
                .setLngLat([result.actual.lng, result.actual.lat])
                .addTo(map);

            map.getSource('guess-line')?.setData({
                type: 'Feature',
                geometry: {
                    type: 'LineString',
                    coordinates: [
                        [result.guessed.lng, result.guessed.lat],
                        [result.actual.lng, result.actual.lat],
                    ],
                },
            });

            const bounds = new maplibregl.LngLatBounds()
                .extend([result.guessed.lng, result.guessed.lat])
                .extend([result.actual.lng, result.actual.lat]);

            // The result bar changes the map height; let the layout settle before fitting.
            await this.$nextTick();
            await new Promise((resolve) => requestAnimationFrame(() => requestAnimationFrame(resolve)));
            map.resize();
            map.fitBounds(bounds, { padding: { top: 60, bottom: 60, left: 50, right: 50 }, maxZoom: 12, duration: 700 });
        },

        reset() {
            this.locked = false;
            this.pending = null;
            map?.getCanvas().classList.remove('cursor-default');
            guessMarker?.remove();
            actualMarker?.remove();
            guessMarker = null;
            actualMarker = null;
            map?.getSource('guess-line')?.setData(emptyLine());
            map?.fitBounds(config.bounds, { padding: 12, duration: 500 });
        },
    };
}

function emptyLine() {
    return { type: 'Feature', geometry: { type: 'LineString', coordinates: [] } };
}
