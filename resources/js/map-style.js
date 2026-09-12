import maplibregl from 'maplibre-gl';

/**
 * Loads the configured MapLibre style and strips every layer that could give
 * the answer away (railway lines, stations, airports). City and geographic
 * labels stay so players can orient themselves.
 */
export async function loadCleanStyle(styleUrl, hiddenLayerPattern = /^(railway|airport|poi|transit)/i) {
    const response = await fetch(styleUrl);
    if (!response.ok) {
        throw new Error(`Kaartstijl niet beschikbaar (${response.status})`);
    }
    const style = await response.json();
    style.layers = style.layers
        .filter((layer) => !hiddenLayerPattern.test(layer.id))
        .map((layer) => dutchLabels(layer));
    return style;
}

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
