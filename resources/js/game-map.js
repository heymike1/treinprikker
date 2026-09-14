import maplibregl from 'maplibre-gl';
import { loadCleanStyle, loadSatelliteStyle, createMap, pinElement } from './map-style';

const PREFERRED_MODE_KEY = 'treinprikker_niveau';
const INTRO_SEEN_KEY = 'treinprikker_intro_gezien';

/**
 * Alpine component that drives the game board: the level picker, the
 * guessing map and the countdown. It never knows the answer until the server
 * returns it after a guess.
 *
 * The MapLibre map and markers live in closure variables on purpose: putting
 * them on the Alpine data object would wrap them in a deep reactive proxy,
 * which makes MapLibre painfully slow.
 */
export default function gameMap(config) {
    let map = null;
    let currentMapStyle = null;
    let guessMarker = null;
    let actualMarker = null;
    let ticker = null;

    return {
        ready: false,
        failed: false,
        pending: null,
        busy: false,
        locked: false,
        error: null,
        intro: false,
        choosing: true,
        deadline: null,
        remaining: 0,

        get hasGuess() {
            return this.pending !== null;
        },

        get clock() {
            const seconds = Math.max(0, Math.ceil(this.remaining));
            return `0:${String(seconds).padStart(2, '0')}`;
        },

        async init() {
            try {
                this.intro = !localStorage.getItem(INTRO_SEEN_KEY);
            } catch {
                this.intro = false;
            }

            // Read the live state from Livewire rather than from x-data: a changing
            // x-data attribute would make Alpine re-initialise this component.
            const mode = this.$wire.mode;
            this.choosing = !mode;

            // Behind the level picker the plain photo already loads (no borders,
            // so nothing hints at a level); the chosen level swaps its style in on start.
            await this.createMap(mode ? config.mapStyles[mode] : 'satellite');
            if (mode) {
                this.startClock(this.$wire.roundDeadline ?? null);
            }
        },

        preferredMode() {
            try {
                const stored = localStorage.getItem(PREFERRED_MODE_KEY);
                if (stored && document.querySelector(`[role="radio"][x-on\\:click*="'${stored}'"]`)) {
                    return stored;
                }
            } catch {
                // Private mode: fall through to the default.
            }
            return 'easy';
        },

        startGame() {
            this.intro = false;
            try {
                localStorage.setItem(INTRO_SEEN_KEY, '1');
            } catch {
                // Private mode: the intro simply shows again next time.
            }
        },

        async choose(mode) {
            if (this.busy) {
                return;
            }
            this.busy = true;
            this.error = null;
            try {
                localStorage.setItem(PREFERRED_MODE_KEY, mode);
            } catch {
                // Not remembering the level is fine.
            }
            try {
                await this.$wire.startGame(mode);
            } catch (error) {
                console.error(error);
                this.error = 'Het spel kon niet worden gestart. Controleer je verbinding en probeer het opnieuw.';
            } finally {
                this.busy = false;
            }
        },

        // Fired by the server once the session exists: show the right map, start the clock.
        async start({ map: mapStyle, deadline }) {
            this.choosing = false;
            await this.createMap(mapStyle);
            this.startClock(deadline ?? null);
        },

        async createMap(mapStyle) {
            if (map) {
                await this.switchStyle(mapStyle);
                return;
            }

            try {
                map = createMap(this.$refs.map, await this.styleFor(mapStyle), config);
                currentMapStyle = mapStyle;
            } catch (error) {
                console.error(error);
                this.failed = true;
                return;
            }

            map.on('load', () => {
                map.fitBounds(config.bounds, { padding: 12, duration: 0 });
                this.addGuessLine();
                this.ready = true;
            });

            map.on('click', (event) => this.place(event.lngLat));
        },

        async switchStyle(mapStyle) {
            if (mapStyle === currentMapStyle) {
                return;
            }

            const style = await this.styleFor(mapStyle);
            currentMapStyle = mapStyle;
            this.ready = false;
            map.once('style.load', () => {
                this.addGuessLine();
                this.ready = true;
            });
            map.setStyle(style);
        },

        styleFor(mapStyle) {
            return mapStyle === 'blank'
                ? loadCleanStyle(config.styleUrl, { blank: true })
                : loadSatelliteStyle(config.satellite, { borders: mapStyle === 'satellite-borders', styleUrl: config.styleUrl });
        },

        // The dashed line between pin and station and the platform outlines
        // shown on reveal; re-added after every style change.
        addGuessLine() {
            if (map.getSource('guess-line')) {
                return;
            }
            map.addSource('platforms', { type: 'geojson', data: emptyCollection() });
            map.addLayer({
                id: 'platforms-fill',
                type: 'fill',
                source: 'platforms',
                paint: { 'fill-color': '#f8c200', 'fill-opacity': 0.35 },
            });
            map.addLayer({
                id: 'platforms-line',
                type: 'line',
                source: 'platforms',
                paint: { 'line-color': '#f8c200', 'line-width': 2 },
            });
            map.addSource('guess-line', { type: 'geojson', data: emptyLine() });
            map.addLayer({
                id: 'guess-line',
                type: 'line',
                source: 'guess-line',
                paint: {
                    'line-color': currentMapStyle === 'blank' ? '#1d3f8f' : '#ffffff',
                    'line-width': 3,
                    'line-dasharray': [1.5, 1.5],
                },
            });
        },

        startClock(deadline) {
            this.stopClock();
            this.deadline = deadline;
            if (deadline === null) {
                return;
            }

            const tick = () => {
                this.remaining = this.deadline - Date.now() / 1000;
                if (this.remaining <= 0) {
                    this.stopClock();
                    this.remaining = 0;
                    this.timeIsUp();
                }
            };
            tick();
            ticker = window.setInterval(tick, 250);
        },

        stopClock() {
            if (ticker) {
                window.clearInterval(ticker);
                ticker = null;
            }
        },

        async timeIsUp() {
            if (this.locked || this.busy) {
                return;
            }
            if (this.hasGuess) {
                await this.submit();
                return;
            }

            this.busy = true;
            try {
                const result = await this.$wire.timeOut();
                await this.handleResult(result);
            } catch (error) {
                console.error(error);
                this.error = 'De tijd was om, maar de ronde kon niet worden afgesloten. Ververs de pagina.';
            } finally {
                this.busy = false;
            }
        },

        place(lngLat) {
            if (this.intro || this.choosing || this.locked || this.busy || !this.ready) {
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
            this.stopClock();
            try {
                const result = await this.$wire.submitGuess(this.pending.lat, this.pending.lng);
                await this.handleResult(result);
            } catch (error) {
                console.error(error);
                this.error = 'De prik kon niet worden verstuurd. Controleer je verbinding en probeer het opnieuw.';
            } finally {
                this.busy = false;
            }
        },

        async handleResult(result) {
            if (result && result.ok) {
                await this.showResult(result);
            } else if (result && result.reload) {
                window.setTimeout(() => window.location.reload(), 1500);
            }
        },

        async showResult(result) {
            this.locked = true;
            this.stopClock();
            this.deadline = null;
            map.getCanvas().classList.add('cursor-default');

            const actual = [result.actual.lng, result.actual.lat];
            actualMarker = new maplibregl.Marker({ element: pinElement('station'), anchor: 'center' })
                .setLngLat(actual)
                .addTo(map);

            const bounds = new maplibregl.LngLatBounds().extend(actual);

            if (result.platforms) {
                map.getSource('platforms')?.setData({
                    type: 'Feature',
                    geometry: { type: 'MultiPolygon', coordinates: result.platforms },
                });
            }

            if (result.guessed) {
                const guessed = [result.guessed.lng, result.guessed.lat];
                guessMarker?.setDraggable(false);
                guessMarker?.setLngLat(guessed);
                map.getSource('guess-line')?.setData({
                    type: 'Feature',
                    geometry: { type: 'LineString', coordinates: [guessed, actual] },
                });
                bounds.extend(guessed);
            } else {
                // Timed out without a pin: only the station is shown.
                guessMarker?.remove();
                guessMarker = null;
            }

            // The result bar changes the map height; let the layout settle before fitting.
            await this.$nextTick();
            await new Promise((resolve) => requestAnimationFrame(() => requestAnimationFrame(resolve)));
            map.resize();
            map.fitBounds(bounds, { padding: { top: 60, bottom: 60, left: 50, right: 50 }, maxZoom: 16, duration: 700 });
        },

        reset(deadline = null) {
            this.locked = false;
            this.pending = null;
            map?.getCanvas().classList.remove('cursor-default');
            guessMarker?.remove();
            actualMarker?.remove();
            guessMarker = null;
            actualMarker = null;
            map?.getSource('guess-line')?.setData(emptyLine());
            map?.getSource('platforms')?.setData(emptyCollection());
            map?.fitBounds(config.bounds, { padding: 12, duration: 500 });
            this.startClock(deadline);
        },
    };
}

function emptyCollection() {
    return { type: 'FeatureCollection', features: [] };
}

function emptyLine() {
    return { type: 'Feature', geometry: { type: 'LineString', coordinates: [] } };
}
