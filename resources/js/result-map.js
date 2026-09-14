/**
 * The finished-game map: the Netherlands as a silhouette with the railway
 * network, one numbered dot per station and a dashed line from each pin to
 * its station, coloured by how close the pin was. Plain SVG, no tiles.
 */
const COLORS = { raak: '#2f8f5b', dichtbij: '#c98700', buurt: '#d96b1a', ver: '#c9432f' };
const WIDTH = 400;
const HEIGHT = 462;

export default function resultMap({ rounds, silhouetteUrl }) {
    return {
        svg: '',
        failed: false,

        async init() {
            try {
                const response = await fetch(silhouetteUrl);
                if (!response.ok) {
                    throw new Error(`Silhouet niet beschikbaar (${response.status})`);
                }
                this.svg = render(await response.json(), rounds);
            } catch (error) {
                console.error(error);
                this.failed = true;
            }
        },
    };
}

function render(outline, rounds) {
    const [west, south, east, north] = outline.bbox;
    const cos = Math.cos((((south + north) / 2) * Math.PI) / 180);
    const scale = Math.min(WIDTH / ((east - west) * cos), HEIGHT / (north - south));
    const project = ([lng, lat]) => [((lng - west) * cos * scale).toFixed(1), ((north - lat) * scale).toFixed(1)];
    const path = (lines, close) => lines.map((line) => 'M' + line.map((p) => project(p).join(',')).join('L') + (close ? 'Z' : '')).join('');

    const parts = [
        `<path d="${path(outline.polygons, true)}" fill="#f6f1e8" stroke="#d8cfbf" stroke-width="0.8"/>`,
        `<path d="${path(outline.rail, false)}" fill="none" stroke="#d5cbbb" stroke-width="0.8" stroke-linecap="round" stroke-linejoin="round"/>`,
    ];

    for (const round of rounds) {
        const color = COLORS[round.bucket] ?? COLORS.ver;
        const [sx, sy] = project([round.station.lng, round.station.lat]);
        if (round.guess) {
            const [gx, gy] = project([round.guess.lng, round.guess.lat]);
            parts.push(
                `<line x1="${gx}" y1="${gy}" x2="${sx}" y2="${sy}" stroke="${color}" stroke-width="1.6" stroke-dasharray="3 2.5" stroke-linecap="round"/>`,
                `<circle cx="${gx}" cy="${gy}" r="3.4" fill="#fffdf9" stroke="${color}" stroke-width="2"/>`,
            );
        }
        parts.push(
            `<circle cx="${sx}" cy="${sy}" r="5.4" fill="${color}" stroke="#fffdf9" stroke-width="1.8"/>`,
            `<text x="${sx}" y="${sy}" dy="0.36em" text-anchor="middle" font-family="ui-monospace, Menlo, monospace" font-size="6.5" font-weight="700" fill="#fffdf9">${round.round}</text>`,
        );
    }

    return `<svg viewBox="0 0 ${WIDTH} ${HEIGHT}" role="img" aria-label="Kaart van Nederland met jouw vijf prikken en de stations" class="block h-auto w-full">${parts.join('')}</svg>`;
}
