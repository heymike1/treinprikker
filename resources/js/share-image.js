/**
 * Draws the shareable result card (1080x1080) on a canvas. Spoiler-free on
 * purpose: scores and colours only, never station names or pin positions.
 */
const COLORS = {
    paper: '#f6f1e8',
    card: '#fffdf9',
    ink: '#1c1a17',
    muted: '#6d655b',
    line: '#e1d8c9',
    rail: '#1d3f8f',
    railDeep: '#142c66',
    signal: '#f8c200',
    buckets: { raak: '#2f8f5b', dichtbij: '#c98700', buurt: '#d96b1a', ver: '#c9432f' },
};

const SANS = "-apple-system, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif";
const MONO = "ui-monospace, 'SF Mono', Menlo, Consolas, monospace";

export async function renderShareImage(data) {
    const size = 1080;
    const canvas = document.createElement('canvas');
    canvas.width = size;
    canvas.height = size;
    const ctx = canvas.getContext('2d');

    ctx.fillStyle = COLORS.paper;
    ctx.fillRect(0, 0, size, size);

    // Departure board with the score.
    const boardHeight = 400;
    ctx.fillStyle = COLORS.rail;
    ctx.fillRect(0, 0, size, boardHeight);

    await drawSilhouette(ctx, data.silhouetteUrl, { x: 640, y: 30, w: 420, h: 350, alpha: 0.12 });

    ctx.fillStyle = 'rgba(246, 241, 232, 0.7)';
    ctx.font = `600 26px ${SANS}`;
    ctx.textBaseline = 'alphabetic';
    drawTracked(ctx, (data.title + (data.modeLabel ? ' · ' + data.modeLabel : '')).toUpperCase(), 72, 96, 4);

    ctx.fillStyle = COLORS.signal;
    ctx.font = `600 200px ${MONO}`;
    ctx.fillText(String(data.totalScore), 64, 290);

    ctx.fillStyle = 'rgba(246, 241, 232, 0.8)';
    ctx.font = `500 30px ${SANS}`;
    drawTracked(ctx, `VAN ${data.maximumScore} PUNTEN`, 72, 350, 3);

    // Route strip: one stop per station, coloured by score.
    const rounds = data.rounds;
    const left = 120;
    const right = size - 120;
    const y = 560;
    ctx.strokeStyle = COLORS.line;
    ctx.lineWidth = 8;
    ctx.lineCap = 'round';
    ctx.beginPath();
    ctx.moveTo(left, y);
    ctx.lineTo(right, y);
    ctx.stroke();

    rounds.forEach((round, index) => {
        const x = rounds.length === 1 ? (left + right) / 2 : left + ((right - left) * index) / (rounds.length - 1);
        const color = round.timedOut ? COLORS.muted : COLORS.buckets[round.bucket] ?? COLORS.muted;

        ctx.beginPath();
        ctx.arc(x, y, 34, 0, Math.PI * 2);
        ctx.fillStyle = COLORS.paper;
        ctx.fill();
        ctx.beginPath();
        ctx.arc(x, y, 26, 0, Math.PI * 2);
        ctx.fillStyle = color;
        ctx.fill();

        ctx.fillStyle = COLORS.ink;
        ctx.font = `600 44px ${MONO}`;
        ctx.textAlign = 'center';
        ctx.fillText(String(round.score), x, y + 100);

        ctx.fillStyle = COLORS.muted;
        ctx.font = `500 22px ${SANS}`;
        ctx.fillText(round.label, x, y + 138);
        ctx.textAlign = 'left';
    });

    // Footer facts.
    const facts = [];
    if (data.betterThan !== null && data.betterThan !== undefined) {
        facts.push(`Beter dan ${data.betterThan}% van de spelers vandaag`);
    }
    if (data.streak > 1) {
        facts.push(`Streak: ${data.streak} dagen`);
    }
    if (facts.length === 0) {
        facts.push('Hoe goed ken jij het Nederlandse spoor?');
    }
    ctx.fillStyle = COLORS.ink;
    ctx.font = `600 34px ${SANS}`;
    facts.forEach((fact, index) => ctx.fillText(fact, 72, 820 + index * 52));

    // Brand line with a small pin.
    drawPin(ctx, 84, 1000, 0.9);
    ctx.fillStyle = COLORS.ink;
    ctx.font = `700 40px ${SANS}`;
    ctx.fillText('Treinprikker', 122, 1013);
    ctx.fillStyle = COLORS.muted;
    ctx.font = `500 30px ${SANS}`;
    ctx.textAlign = 'right';
    ctx.fillText('treinprikker.nl', size - 72, 1013);
    ctx.textAlign = 'left';

    return new Promise((resolve) => canvas.toBlob(resolve, 'image/png'));
}

function drawTracked(ctx, text, x, y, spacing) {
    for (const char of text) {
        ctx.fillText(char, x, y);
        x += ctx.measureText(char).width + spacing;
    }
}

function drawPin(ctx, x, y, scale) {
    ctx.save();
    ctx.translate(x, y);
    ctx.scale(scale, scale);
    ctx.beginPath();
    ctx.moveTo(0, 22);
    ctx.bezierCurveTo(-4, 12, -20, 0, -20, -12);
    ctx.arc(0, -12, 20, Math.PI, 0);
    ctx.bezierCurveTo(20, 0, 4, 12, 0, 22);
    ctx.closePath();
    ctx.fillStyle = COLORS.signal;
    ctx.fill();
    ctx.lineWidth = 3;
    ctx.strokeStyle = COLORS.ink;
    ctx.stroke();
    ctx.beginPath();
    ctx.arc(0, -12, 7, 0, Math.PI * 2);
    ctx.fillStyle = COLORS.ink;
    ctx.fill();
    ctx.restore();
}

async function drawSilhouette(ctx, url, box) {
    let outline;
    try {
        outline = await fetch(url).then((response) => response.json());
    } catch {
        return; // Purely decorative: skip when unavailable.
    }

    const [west, south, east, north] = outline.bbox;
    // Correct the aspect for latitude so the country isn't squashed.
    const cos = Math.cos(((south + north) / 2) * (Math.PI / 180));
    const scale = Math.min(box.w / ((east - west) * cos), box.h / (north - south));
    const width = (east - west) * cos * scale;
    const height = (north - south) * scale;
    const offsetX = box.x + (box.w - width) / 2;
    const offsetY = box.y + (box.h - height) / 2;

    ctx.save();
    ctx.globalAlpha = box.alpha;
    ctx.fillStyle = '#ffffff';
    ctx.beginPath();
    for (const ring of outline.polygons) {
        ring.forEach(([lng, lat], index) => {
            const x = offsetX + (lng - west) * cos * scale;
            const y = offsetY + (north - lat) * scale;
            index === 0 ? ctx.moveTo(x, y) : ctx.lineTo(x, y);
        });
        ctx.closePath();
    }
    ctx.fill();
    ctx.restore();
}
