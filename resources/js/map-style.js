import maplibregl from 'maplibre-gl';

// Layers that could give the answer away on any map: railways, stations, airports.
const ANSWER_LAYERS = /^(railway|airport|poi|transit)/i;

// Layers that make guessing too easy on the game map: place names and street names.
// Country/state labels, water names and road shields (A2, N33) stay for orientation.
const PLACE_LAYERS = /^(label_(city|town|village|other)|highway-name)/i;

// The "blank" expert map keeps only these: land, water and administrative borders,
// plus the railway lines (without stations) added below.
const BLANK_LAYERS = /^(background|water|waterway|boundary_2|boundary_3)$/;

/**
 * Loads the configured MapLibre style and strips every layer that could give
 * the answer away. With `hidePlaces` the map also loses city and street names;
 * with `blank` nothing but land, water and borders is left.
 */
export async function loadCleanStyle(styleUrl, { hidePlaces = false, blank = false } = {}) {
    const response = await fetch(styleUrl);
    if (!response.ok) {
        throw new Error(`Kaartstijl niet beschikbaar (${response.status})`);
    }
    const style = await response.json();
    style.layers = style.layers
        .filter((layer) => !ANSWER_LAYERS.test(layer.id))
        .filter((layer) => !(hidePlaces && PLACE_LAYERS.test(layer.id)))
        .filter((layer) => !blank || BLANK_LAYERS.test(layer.id))
        .map((layer) => dutchLabels(layer));
    if (blank) {
        style.layers = style.layers.map((layer) => blankPaint(layer));
        // The vector tiles only carry railways from zoom ~9, so the blank map
        // brings its own simplified network (public/data/spoornet.json).
        style.sources.spoornet = { type: 'geojson', data: '/data/spoornet.json' };
        style.layers.push({
            id: 'spoornet',
            type: 'line',
            source: 'spoornet',
            layout: { 'line-cap': 'round', 'line-join': 'round' },
            paint: {
                'line-color': '#4a4540',
                'line-width': ['interpolate', ['linear'], ['zoom'], 6, 0.9, 9, 1.5, 12, 2.4],
                'line-opacity': 0.85,
            },
        });
    }
    return style;
}

/**
 * Warm paper land, soft blue water and grey borders for the blank map.
 */
function blankPaint(layer) {
    const paint = {
        background: { 'background-color': '#ede6d9' },
        water: { 'fill-color': '#b9cfe0' },
        waterway: { 'line-color': '#b9cfe0', 'line-width': 1 },
        boundary_2: { 'line-color': '#6d655b', 'line-width': 1.5 },
        boundary_3: { 'line-color': '#a39a8e', 'line-width': 1, 'line-dasharray': [3, 2] },
    }[layer.id];

    if (!paint) {
        return layer;
    }

    // Province borders help orientation, so show them from the overview zoom.
    const minzoom = layer.id === 'boundary_3' ? 5 : layer.minzoom;

    return { ...layer, paint, ...(minzoom !== undefined ? { minzoom } : {}) };
}

/**
 * Aerial imagery for the game map: nothing but the photo. It shows the
 * landscape, the cities and the railway lines by itself.
 */
/**
 * Prefer Dutch names ("Den Haag" instead of "The Hague") on every label layer.
 */
function dutchLabels(layer) {
    if (layer.type !== 'symbol' || !layer.layout || !layer.layout['text-field']) {
        return layer;
    }
    if (layer.id.startsWith('highway-shield') || layer.id.startsWith('road_shield')) {
        return layer;
    }

    return {
        ...layer,
        layout: {
            ...layer.layout,
            'text-field': ['coalesce', ['get', 'name:nl'], ['get', 'name:latin'], ['get', 'name']],
        },
    };
}

/**
 * Aerial imagery for the game map: nothing but the photo, which shows the
 * landscape, the cities and the railway lines by itself. With `borders` the
 * national border from the vector tiles is drawn on top.
 */
export async function loadSatelliteStyle(satellite, { borders = false, styleUrl = null } = {}) {
    const style = {
        version: 8,
        sources: {
            satellite: {
                type: 'raster',
                tiles: satellite.tiles,
                tileSize: 256,
                maxzoom: satellite.max_zoom,
                attribution: satellite.attribution,
            },
        },
        layers: [
            { id: 'background', type: 'background', paint: { 'background-color': '#1f2a33' } },
            { id: 'satellite', type: 'raster', source: 'satellite', paint: { 'raster-saturation': -0.15 } },
        ],
    };

    if (!borders || !styleUrl) {
        return style;
    }

    const response = await fetch(styleUrl);
    if (!response.ok) {
        // Borders are a nicety; the photo alone is a complete map.
        return style;
    }
    const vector = await response.json();
    const boundary = vector.layers.find((layer) => layer.id === 'boundary_2');
    if (!boundary || !vector.sources[boundary.source]) {
        return style;
    }

    style.sources[boundary.source] = vector.sources[boundary.source];
    style.layers.push({
        id: 'border-country',
        type: 'line',
        source: boundary.source,
        'source-layer': boundary['source-layer'],
        filter: ['all', ['==', ['get', 'admin_level'], 2], ['!=', ['get', 'maritime'], 1], ['!=', ['get', 'disputed'], 1]],
        layout: { 'line-cap': 'round', 'line-join': 'round' },
        paint: {
            'line-color': '#ffffff',
            'line-opacity': 0.85,
            'line-width': ['interpolate', ['linear'], ['zoom'], 5, 1.4, 10, 2.6],
        },
    });

    return style;
}

export const DUTCH_LOCALE = {
    'AttributionControl.ToggleAttribution': 'Bronvermelding tonen',
    'NavigationControl.ZoomIn': 'Inzoomen',
    'NavigationControl.ZoomOut': 'Uitzoomen',
    'NavigationControl.ResetBearing': 'Kaart rechtzetten',
    'FullscreenControl.Enter': 'Volledig scherm',
    'FullscreenControl.Exit': 'Volledig scherm sluiten',
};

export function createMap(container, style, options) {
    const map = new maplibregl.Map({
        container,
        style,
        center: options.center,
        zoom: options.zoom,
        minZoom: options.minZoom,
        maxZoom: options.maxZoom,
        maxBounds: [[-1.5, 48.5], [12, 55.5]],
        dragRotate: false,
        pitchWithRotate: false,
        touchPitch: false,
        attributionControl: false,
        locale: DUTCH_LOCALE,
    });

    map.touchZoomRotate.disableRotation();
    map.addControl(new maplibregl.NavigationControl({ showCompass: false }), 'top-right');
    map.addControl(new maplibregl.AttributionControl({ compact: true }), 'bottom-left');

    return map;
}

export function pinElement(kind) {
    const el = document.createElement('div');
    el.className = `map-pin map-pin-${kind}`;
    el.setAttribute('role', 'img');
    el.setAttribute('aria-label', kind === 'guess' ? 'Jouw prik' : 'Het station');
    return el;
}
