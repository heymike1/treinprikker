/**
 * Draws the shareable result card (1080x1080) on a canvas: a ticket on a
 * railway-blue background with the score, the five stations as stops on a
 * route, distance and points.
 */
const COLORS = {
    paper: '#f6f1e8',
    card: '#fffdf9',
    ink: '#1c1a17',
    muted: '#6d655b',
    line: '#e1d8c9',
    rail: '#1d3f8f',
    signal: '#f8c200',
    buckets: { raak: '#2f8f5b', dichtbij: '#c98700', buurt: '#d96b1a', ver: '#c9432f' },
};

const SANS = "-apple-system, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif";
const MONO = "ui-monospace, 'SF Mono', Menlo, Consolas, monospace";

const SIZE = 1080;
const CARD = { x: 72, w: 936, radius: 28 };
const HEADER_HEIGHT = 216;
const ROW_HEIGHT = 98;
const ROWS_PADDING = { top: 44, bottom: 30 };
const FOOTER_HEIGHT = 96;

export async function renderShareImage(data) {
    const canvas = document.createElement('canvas');
    canvas.width = SIZE;
    canvas.height = SIZE;
    const ctx = canvas.getContext('2d');

    ctx.fillStyle = COLORS.rail;
    ctx.fillRect(0, 0, SIZE, SIZE);
    await drawSilhouette(ctx, data.silhouetteUrl, { x: -120, y: 620, w: 640, h: 620, alpha: 0.08 });

    const rows = data.rounds;
    const rowsHeight = ROWS_PADDING.top + rows.length * ROW_HEIGHT + ROWS_PADDING.bottom;
    const cardHeight = HEADER_HEIGHT + rowsHeight + FOOTER_HEIGHT;
    const card = { ...CARD, y: Math.round((SIZE - cardHeight) / 2), h: cardHeight };

    drawCard(ctx, card);
    drawHeader(ctx, card, data);
    drawPerforation(ctx, card, card.y + HEADER_HEIGHT);
    drawRows(ctx, card, rows, card.y + HEADER_HEIGHT + ROWS_PADDING.top);
    drawFooter(ctx, card, data, card.y + card.h - FOOTER_HEIGHT);

    return new Promise((resolve) => canvas.toBlob(resolve, 'image/png'));
}

function drawCard(ctx, card) {
    ctx.save();
    ctx.shadowColor = 'rgba(0, 0, 0, 0.3)';
    ctx.shadowBlur = 60;
    ctx.shadowOffsetY = 30;
    roundedRect(ctx, card.x, card.y, card.w, card.h, card.radius);
    ctx.fillStyle = COLORS.card;
    ctx.fill();
    ctx.restore();
}

function drawHeader(ctx, card, data) {
    ctx.save();
    roundedRect(ctx, card.x, card.y, card.w, card.h, card.radius);
    ctx.clip();
    ctx.fillStyle = COLORS.signal;
    ctx.fillRect(card.x, card.y, card.w, HEADER_HEIGHT);
    ctx.restore();

    const left = card.x + 48;
    const right = card.x + card.w - 48;
    const title = ('Treinprikker' + (data.modeLabel ? ' · ' + data.modeLabel : '')).toUpperCase();

    ctx.fillStyle = 'rgba(28, 26, 23, 0.6)';
    ctx.font = `700 22px ${SANS}`;
    drawTracked(ctx, title, left, card.y + 66, 3);
    ctx.textAlign = 'right';
    drawTracked(ctx, 'VANDAAG', right, card.y + 66, 3, true);
    ctx.textAlign = 'left';

    ctx.fillStyle = COLORS.ink;
    ctx.font = `600 128px ${MONO}`;
    ctx.fillText(String(data.totalScore), left - 6, card.y + 176);
    const scoreWidth = ctx.measureText(String(data.totalScore)).width;
    ctx.fillStyle = 'rgba(28, 26, 23, 0.5)';
    ctx.font = `600 44px ${MONO}`;
    ctx.fillText(`/ ${data.maximumScore}`, left + scoreWidth + 8, card.y + 176);

    ctx.fillStyle = COLORS.ink;
    ctx.font = `600 44px ${MONO}`;
    ctx.textAlign = 'right';
    ctx.fillText(data.date, right, card.y + 130);
    ctx.textAlign = 'left';
}

function drawPerforation(ctx, card, y) {
    ctx.save();
    ctx.strokeStyle = COLORS.line;
    ctx.lineWidth = 4;
    ctx.setLineDash([14, 12]);
    ctx.beginPath();
    ctx.moveTo(card.x + 30, y);
    ctx.lineTo(card.x + card.w - 30, y);
    ctx.stroke();
    ctx.restore();

    ctx.fillStyle = COLORS.rail;
    for (const x of [card.x, card.x + card.w]) {
        ctx.beginPath();
        ctx.arc(x, y, 22, 0, Math.PI * 2);
        ctx.fill();
    }
}

function drawRows(ctx, card, rows, top) {
    const stopX = card.x + 48 + 22;
    const nameX = card.x + 48 + 44 + 24;
    const scoreX = card.x + card.w - 48;
    const nameMaxWidth = scoreX - 190 - 24 - nameX;

    // The route line first, behind the stops.
    if (rows.length > 1) {
        ctx.strokeStyle = COLORS.line;
        ctx.lineWidth = 6;
        ctx.beginPath();
        ctx.moveTo(stopX, top + 22);
        ctx.lineTo(stopX, top + (rows.length - 1) * ROW_HEIGHT + 22);
        ctx.stroke();
    }

    rows.forEach((round, index) => {
        const y = top + index * ROW_HEIGHT + 22;
        const color = round.timedOut ? COLORS.muted : COLORS.buckets[round.bucket] ?? COLORS.muted;

        ctx.beginPath();
        ctx.arc(stopX, y, 22, 0, Math.PI * 2);
        ctx.fillStyle = color;
        ctx.fill();
        ctx.beginPath();
        ctx.arc(stopX, y, 18, 0, Math.PI * 2);
        ctx.fillStyle = COLORS.card;
        ctx.fill();
        ctx.beginPath();
        ctx.arc(stopX, y, 12, 0, Math.PI * 2);
        ctx.fillStyle = color;
        ctx.fill();

        ctx.textBaseline = 'alphabetic';
        ctx.fillStyle = COLORS.ink;
        ctx.font = `700 40px ${SANS}`;
        ctx.fillText(fit(ctx, round.station, nameMaxWidth), nameX, y + 6);
        ctx.fillStyle = COLORS.muted;
        ctx.font = `500 26px ${SANS}`;
        ctx.fillText(round.distance, nameX, y + 44);

        ctx.textAlign = 'right';
        ctx.fillStyle = COLORS.ink;
        ctx.font = `600 52px ${MONO}`;
        ctx.fillText(String(round.score), scoreX, y + 12);
        ctx.fillStyle = color;
        ctx.font = `600 22px ${SANS}`;
        ctx.fillText(round.label, scoreX, y + 44);
        ctx.textAlign = 'left';
    });
}

function drawFooter(ctx, card, data, top) {
    const left = card.x + 48;
    const right = card.x + card.w - 48;

    ctx.strokeStyle = COLORS.line;
    ctx.lineWidth = 2;
    ctx.beginPath();
    ctx.moveTo(card.x, top);
    ctx.lineTo(card.x + card.w, top);
    ctx.stroke();

    const y = top + FOOTER_HEIGHT / 2 + 10;
    const fact = data.betterThan !== null && data.betterThan !== undefined
        ? `Beter dan ${data.betterThan}% van de spelers vandaag`
        : 'Hoe goed ken jij het Nederlandse spoor?';
    ctx.fillStyle = COLORS.muted;
    ctx.font = `600 26px ${SANS}`;
    ctx.fillText(fit(ctx, fact, 560), left, y);

    ctx.fillStyle = COLORS.ink;
    ctx.font = `700 30px ${SANS}`;
    ctx.textAlign = 'right';
    ctx.fillText('treinprikker.nl', right, y + 1);
    const brandWidth = ctx.measureText('treinprikker.nl').width;
    ctx.textAlign = 'left';
    drawPin(ctx, right - brandWidth - 28, y - 4, 0.8);
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
