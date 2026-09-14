/**
 * Draws the shareable result card (1080x1350) on a canvas, in the style of
 * the finished-game screen but on the dark departure board: the verdict and
 * score, the map of the Netherlands with the five pins, and the station list.
 */
const COLORS = {
    paper: '#f6f1e8',
    card: '#fffdf9',
    railDeep: '#142c66',
    board: '#0f1f4d',
    land: '#1a3576',
    coast: '#2b478f',
    track: '#3b57a3',
    signal: '#f8c200',
    muted: 'rgba(246, 241, 232, 0.6)',
    faint: 'rgba(246, 241, 232, 0.14)',
    buckets: { raak: '#2f8f5b', dichtbij: '#c98700', buurt: '#d96b1a', ver: '#c9432f' },
};

const SANS = "-apple-system, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif";
const MONO = "ui-monospace, 'SF Mono', Menlo, Consolas, monospace";

const WIDTH = 1080;
const HEIGHT = 1350;
const PADDING = 64;
const GAP = 28;
const MAP = { w: 360, h: 416, padding: 16 };
const ROW_HEIGHT = 87;

export async function renderShareImage(data) {
    const canvas = document.createElement('canvas');
    canvas.width = WIDTH;
    canvas.height = HEIGHT;
    const ctx = canvas.getContext('2d');
    const left = PADDING;
    const right = WIDTH - PADDING;

    ctx.fillStyle = COLORS.railDeep;
    ctx.fillRect(0, 0, WIDTH, HEIGHT);
    ctx.textBaseline = 'alphabetic';

    // Header: brand left, game number, date and level right.
    let y = PADDING;
    drawPin(ctx, left + 20, y + 24, 1);
    ctx.fillStyle = COLORS.paper;
    ctx.font = `700 30px ${SANS}`;
    ctx.textAlign = 'left';
    ctx.fillText('Treinprikker', left + 54, y + 33);
    ctx.fillStyle = COLORS.muted;
    ctx.font = `500 24px ${MONO}`;
    ctx.textAlign = 'right';
    ctx.fillText(`#${data.gameNumber} · ${data.date} · ${data.modeLabel}`, right, y + 31);
    y += 44 + GAP;

    // Verdict left, score right; the score's baseline sits on the last headline line.
    ctx.textAlign = 'left';
    ctx.font = `700 96px ${MONO}`;
    const scoreWidth = ctx.measureText(String(data.totalScore)).width;
    ctx.font = `800 54px ${SANS}`;
    const headline = wrap(ctx, data.headline, right - left - scoreWidth - 32, 2);
    const blockHeight = 22 + 8 + headline.length * 57;
    ctx.fillStyle = COLORS.muted;
    ctx.font = `600 22px ${SANS}`;
    drawTracked(ctx, 'KLAAR VOOR VANDAAG', left, y + 22, 2.6);
    ctx.fillStyle = COLORS.paper;
    ctx.font = `800 54px ${SANS}`;
    headline.forEach((line, index) => ctx.fillText(line, left, y + 22 + 8 + 48 + index * 57));
    const baseline = y + 22 + 8 + 48 + (headline.length - 1) * 57;
    ctx.textAlign = 'right';
    ctx.fillStyle = COLORS.signal;
    ctx.font = `700 96px ${MONO}`;
    ctx.fillText(String(data.totalScore), right, baseline);
    ctx.fillStyle = COLORS.muted;
    ctx.font = `500 22px ${MONO}`;
    ctx.fillText(`van ${data.maximumScore}`, right, baseline + 30);
    ctx.textAlign = 'left';
    y += blockHeight + GAP;

    // Board: map on top, station rows below.
    const footerTop = HEIGHT - PADDING - 30;
    const boardBottom = footerTop - GAP;
    roundedRect(ctx, left, y, right - left, boardBottom - y, 28);
    ctx.fillStyle = COLORS.board;
    ctx.fill();

    await drawMap(ctx, data, { x: (WIDTH - MAP.w) / 2, y: y + MAP.padding, w: MAP.w, h: MAP.h });

    const rowsTop = y + MAP.padding * 2 + MAP.h + 8;
    const rowLeft = left + 32;
    const rowRight = right - 32;
    data.rounds.forEach((round, index) => {
        const top = rowsTop + index * ROW_HEIGHT;
        const middle = top + ROW_HEIGHT / 2;
        const color = round.timedOut ? COLORS.muted : COLORS.buckets[round.bucket] ?? COLORS.buckets.ver;

        if (index > 0) {
            ctx.strokeStyle = COLORS.faint;
            ctx.lineWidth = 1;
            ctx.beginPath();
            ctx.moveTo(rowLeft, top);
            ctx.lineTo(rowRight, top);
            ctx.stroke();
        }

        ctx.beginPath();
        ctx.arc(rowLeft + 20, middle, 20, 0, Math.PI * 2);
        ctx.fillStyle = color;
        ctx.fill();
        ctx.fillStyle = COLORS.card;
        ctx.font = `700 20px ${MONO}`;
        ctx.textAlign = 'center';
        ctx.fillText(String(index + 1), rowLeft + 20, middle + 7);

        ctx.textAlign = 'left';
        const nameX = rowLeft + 56;
        ctx.fillStyle = COLORS.paper;
        ctx.font = `700 30px ${SANS}`;
        ctx.fillText(fit(ctx, round.station, rowRight - 140 - nameX), nameX, middle - 2);
        ctx.fillStyle = COLORS.muted;
        ctx.font = `400 21px ${SANS}`;
        ctx.fillText(`${round.distance} · ${round.label}`, nameX, middle + 26);

        ctx.textAlign = 'right';
        ctx.fillStyle = COLORS.signal;
        ctx.font = `600 34px ${MONO}`;
        ctx.fillText(String(round.score), rowRight, middle + 12);
        ctx.textAlign = 'left';
    });

    // Footer.
    ctx.fillStyle = COLORS.muted;
    ctx.font = `400 24px ${SANS}`;
    ctx.fillText('Hoe goed ken jij het Nederlandse spoor?', left, footerTop + 24);
    ctx.fillStyle = COLORS.paper;
    ctx.font = `600 24px ${MONO}`;
    ctx.textAlign = 'right';
    ctx.fillText('treinprikker.nl', right, footerTop + 24);
    ctx.textAlign = 'left';

    return new Promise((resolve) => canvas.toBlob(resolve, 'image/png'));
}

async function drawMap(ctx, data, box) {
    let outline;
    try {
        outline = await fetch(data.silhouetteUrl).then((response) => response.json());
    } catch {
        return; // The card still reads without the map.
    }

    const [west, south, east, north] = outline.bbox;
    const cos = Math.cos(((south + north) / 2) * (Math.PI / 180));
    const scale = Math.min(box.w / ((east - west) * cos), box.h / (north - south));
    const width = (east - west) * cos * scale;
    const height = (north - south) * scale;
    const offsetX = box.x + (box.w - width) / 2;
    const offsetY = box.y + (box.h - height) / 2;
    const project = ([lng, lat]) => [offsetX + (lng - west) * cos * scale, offsetY + (north - lat) * scale];
    const trace = (lines, close) => {
        ctx.beginPath();
        for (const line of lines) {
            line.forEach((point, index) => {
                const [x, y] = project(point);
                index === 0 ? ctx.moveTo(x, y) : ctx.lineTo(x, y);
            });
            if (close) {
                ctx.closePath();
            }
        }
    };

    ctx.save();
    ctx.lineJoin = 'round';
    ctx.lineCap = 'round';
    trace(outline.polygons, true);
    ctx.fillStyle = COLORS.land;
    ctx.fill();
    ctx.strokeStyle = COLORS.coast;
    ctx.lineWidth = 1;
    ctx.stroke();
    trace(outline.rail ?? [], false);
    ctx.strokeStyle = COLORS.track;
    ctx.lineWidth = 1;
    ctx.stroke();

    for (const round of data.map ?? []) {
        const color = COLORS.buckets[round.bucket] ?? COLORS.buckets.ver;
        const [sx, sy] = project([round.station.lng, round.station.lat]);
        if (round.guess) {
            const [gx, gy] = project([round.guess.lng, round.guess.lat]);
            ctx.setLineDash([4, 3]);
            ctx.strokeStyle = color;
            ctx.lineWidth = 2;
            ctx.beginPath();
            ctx.moveTo(gx, gy);
            ctx.lineTo(sx, sy);
            ctx.stroke();
            ctx.setLineDash([]);
            ctx.beginPath();
            ctx.arc(gx, gy, 4.5, 0, Math.PI * 2);
            ctx.fillStyle = COLORS.railDeep;
            ctx.fill();
            ctx.stroke();
        }
        ctx.beginPath();
        ctx.arc(sx, sy, 7.5, 0, Math.PI * 2);
        ctx.fillStyle = color;
        ctx.fill();
        ctx.strokeStyle = COLORS.railDeep;
        ctx.lineWidth = 2;
        ctx.stroke();
        ctx.fillStyle = COLORS.card;
        ctx.font = `700 9px ${MONO}`;
        ctx.textAlign = 'center';
        ctx.fillText(String(round.round), sx, sy + 3.5);
        ctx.textAlign = 'left';
    }
    ctx.restore();
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

// Breaks text into at most `maxLines` lines that fit; the last line gets an ellipsis if needed.
function wrap(ctx, text, maxWidth, maxLines) {
    const words = text.split(' ');
    const lines = [];
    let current = '';
    for (const word of words) {
        const candidate = current ? `${current} ${word}` : word;
        if (ctx.measureText(candidate).width <= maxWidth || !current) {
            current = candidate;
        } else {
            lines.push(current);
            current = word;
        }
    }
    lines.push(current);
    if (lines.length > maxLines) {
        const kept = lines.slice(0, maxLines);
        kept[maxLines - 1] = fit(ctx, lines.slice(maxLines - 1).join(' '), maxWidth);
        return kept;
    }
    return lines;
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

function drawTracked(ctx, text, x, y, spacing) {
    let cursor = x;
    for (const char of [...text]) {
        ctx.fillText(char, cursor, y);
        cursor += ctx.measureText(char).width + spacing;
    }
}

// The logo pin: light pin, dark train window and legs, as in the site header on dark.
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
    ctx.fillStyle = COLORS.paper;
    ctx.fill();
    roundedRect(ctx, -9, -20, 18, 16, 4);
    ctx.fillStyle = COLORS.railDeep;
    ctx.fill();
    ctx.beginPath();
    ctx.arc(-4.5, 0, 1.8, 0, Math.PI * 2);
    ctx.arc(4.5, 0, 1.8, 0, Math.PI * 2);
    ctx.fill();
    ctx.restore();
}
