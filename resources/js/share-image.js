/**
 * Draws the shareable result card (1080x1080) on a canvas: a dark departure
 * board with the score, the five stations with distance and points, and the
 * Dutch railway network as a silhouette.
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

const SIZE = 1080;
const PADDING = { x: 72, top: 64, bottom: 56 };
const ROW_HEIGHT = 118;

export async function renderShareImage(data) {
    const canvas = document.createElement('canvas');
    canvas.width = SIZE;
    canvas.height = SIZE;
    const ctx = canvas.getContext('2d');

    ctx.fillStyle = COLORS.railDeep;
    ctx.fillRect(0, 0, SIZE, SIZE);
    await drawSilhouette(ctx, data.silhouetteUrl, { x: 520, y: -40, w: 560, h: 560 });
    ctx.fillStyle = COLORS.signal;
    ctx.fillRect(0, 0, SIZE, 8);

    const left = PADDING.x;
    const right = SIZE - PADDING.x;

    // Header: title, date, big score.
    ctx.fillStyle = 'rgba(246, 241, 232, 0.7)';
    ctx.font = `600 26px ${SANS}`;
    drawTracked(ctx, ('Treinprikker' + (data.modeLabel ? ' · ' + data.modeLabel : '')).toUpperCase(), left, PADDING.top + 26, 4);
    ctx.fillStyle = 'rgba(246, 241, 232, 0.55)';
    ctx.font = `500 26px ${MONO}`;
    ctx.textAlign = 'right';
    ctx.fillText(data.date, right, PADDING.top + 26);
    ctx.textAlign = 'left';

    ctx.fillStyle = COLORS.signal;
    ctx.font = `600 220px ${MONO}`;
    ctx.fillText(String(data.totalScore), left - 8, PADDING.top + 250);
    const scoreWidth = ctx.measureText(String(data.totalScore)).width;
    ctx.fillStyle = 'rgba(246, 241, 232, 0.55)';
    ctx.font = `500 40px ${MONO}`;
    ctx.fillText(`/ ${data.maximumScore}`, left + scoreWidth + 12, PADDING.top + 250);

    // Board rows: one per station.
    const rowsTop = PADDING.top + 300;
    ctx.strokeStyle = 'rgba(246, 241, 232, 0.2)';
    ctx.lineWidth = 2;
    ctx.beginPath();
    ctx.moveTo(left, rowsTop);
    ctx.lineTo(right, rowsTop);
    ctx.stroke();

    data.rounds.forEach((round, index) => {
        const top = rowsTop + index * ROW_HEIGHT;
        const y = top + ROW_HEIGHT / 2;
        const color = round.timedOut ? COLORS.muted : COLORS.buckets[round.bucket] ?? COLORS.muted;

        if (index > 0) {
            ctx.strokeStyle = 'rgba(246, 241, 232, 0.14)';
            ctx.lineWidth = 1;
            ctx.beginPath();
            ctx.moveTo(left, top);
            ctx.lineTo(right, top);
            ctx.stroke();
        }

        ctx.beginPath();
        ctx.arc(left + 14, y, 18, 0, Math.PI * 2);
        ctx.fillStyle = 'rgba(246, 241, 232, 0.12)';
        ctx.fill();
        ctx.beginPath();
        ctx.arc(left + 14, y, 14, 0, Math.PI * 2);
        ctx.fillStyle = color;
        ctx.fill();

        const nameX = left + 56;
        ctx.textBaseline = 'alphabetic';
        ctx.fillStyle = COLORS.paper;
        ctx.font = `700 40px ${SANS}`;
        ctx.fillText(fit(ctx, round.station, right - 220 - nameX), nameX, y + 2);
        ctx.fillStyle = 'rgba(246, 241, 232, 0.6)';
        ctx.font = `500 24px ${MONO}`;
        ctx.fillText(round.distance, nameX, y + 38);

        ctx.textAlign = 'right';
        ctx.fillStyle = COLORS.signal;
        ctx.font = `600 56px ${MONO}`;
        ctx.fillText(String(round.score), right, y + 10);
        ctx.fillStyle = 'rgba(246, 241, 232, 0.55)';
        ctx.font = `600 20px ${SANS}`;
        drawTracked(ctx, round.label.toUpperCase(), right, y + 40, 2, true);
        ctx.textAlign = 'left';
    });

    // Footer: comparison chip and brand.
    const footerY = SIZE - PADDING.bottom - 20;
    const fact = data.rankingLabel ?? 'Hoe goed ken jij het Nederlandse spoor?';
    ctx.font = `600 24px ${SANS}`;
    const factWidth = ctx.measureText(fact).width;
    roundedRect(ctx, left, footerY - 30, factWidth + 36, 50, 25);
    ctx.fillStyle = 'rgba(246, 241, 232, 0.12)';
    ctx.fill();
    ctx.fillStyle = COLORS.paper;
    ctx.fillText(fact, left + 18, footerY + 4);

    ctx.fillStyle = COLORS.paper;
    ctx.font = `700 32px ${SANS}`;
    ctx.textAlign = 'right';
    ctx.fillText('treinprikker.nl', right, footerY + 6);
    const brandWidth = ctx.measureText('treinprikker.nl').width;
    ctx.textAlign = 'left';
    drawPin(ctx, right - brandWidth - 30, footerY - 2, 0.9, COLORS.railDeep);

    return new Promise((resolve) => canvas.toBlob(resolve, 'image/png'));
}

function roundedRect(ctx, x, y, w, h, r) {
    ctx.beginPath();
    ctx.moveTo(x + r, y);
    ctx.arcTo(x + w, y, x + w, y + h, r);
    ctx.arcTo(x + w, y + h, x, y + h, r);
    ctx.arcTo(x, y + h, x, y, r);
    ctx.arcTo(x, y, x + w, y, r);
    ctx.closePath();
}

// Shortens a label with an ellipsis so it fits the given width.
function fit(ctx, text, maxWidth) {
    if (ctx.measureText(text).width <= maxWidth) {
        return text;
    }
    let shortened = text;
    while (shortened.length > 1 && ctx.measureText(shortened + '…').width > maxWidth) {
        shortened = shortened.slice(0, -1);
    }
    return shortened.trimEnd() + '…';
}

function drawTracked(ctx, text, x, y, spacing, alignRight = false) {
    const chars = [...text];
    const width = chars.reduce((sum, char) => sum + ctx.measureText(char).width + spacing, -spacing);
    let cursor = alignRight ? x - width : x;
    const align = ctx.textAlign;
    ctx.textAlign = 'left';
    for (const char of chars) {
        ctx.fillText(char, cursor, y);
        cursor += ctx.measureText(char).width + spacing;
    }
    ctx.textAlign = align;
}

function drawPin(ctx, x, y, scale, outline = COLORS.ink) {
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
    ctx.strokeStyle = outline;
    ctx.stroke();
    ctx.beginPath();
    ctx.arc(0, -12, 7, 0, Math.PI * 2);
    ctx.fillStyle = outline;
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

    const project = ([lng, lat]) => [offsetX + (lng - west) * cos * scale, offsetY + (north - lat) * scale];

    ctx.save();
    ctx.fillStyle = 'rgba(255, 255, 255, 0.07)';
    ctx.beginPath();
    for (const ring of outline.polygons) {
        ring.forEach((point, index) => {
            const [x, y] = project(point);
            index === 0 ? ctx.moveTo(x, y) : ctx.lineTo(x, y);
        });
        ctx.closePath();
    }
    ctx.fill();

    // The railway network inside the silhouette.
    ctx.strokeStyle = 'rgba(255, 255, 255, 0.28)';
    ctx.lineWidth = 1.6;
    ctx.lineJoin = 'round';
    ctx.lineCap = 'round';
    ctx.beginPath();
    for (const line of outline.rail ?? []) {
        line.forEach((point, index) => {
            const [x, y] = project(point);
            index === 0 ? ctx.moveTo(x, y) : ctx.lineTo(x, y);
        });
    }
    ctx.stroke();
    ctx.restore();
}
