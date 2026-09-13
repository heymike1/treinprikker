/**
 * Instagram-ready images drawn on a canvas, in the house style:
 * a weekly station ranking (1080x1080) and the three "zo werkt het" slides (1080x1350).
 */
const C = {
    paper: '#f6f1e8',
    paperDeep: '#ede6d9',
    ink: '#1c1a17',
    muted: '#6d655b',
    line: '#e1d8c9',
    rail: '#084b82',
    board: '#142c66',
    signal: '#f8c200',
};
const SANS = "-apple-system, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif";
const MONO = "ui-monospace, 'SF Mono', Menlo, Consolas, monospace";

let outlinePromise = null;
function outline(url) {
    outlinePromise ??= fetch(url).then((r) => r.json()).catch(() => null);
    return outlinePromise;
}

function canvas(w, h) {
    const el = document.createElement('canvas');
    el.width = w;
    el.height = h;
    return [el, el.getContext('2d')];
}

function brandBar(ctx, w, dark) {
    drawLogo(ctx, 72, 72, 44, dark ? C.signal : C.rail);
    ctx.fillStyle = dark ? C.paper : C.ink;
    ctx.font = `700 30px ${SANS}`;
    ctx.textBaseline = 'middle';
    ctx.fillText('Treinprikker', 72 + 54, 94);
    ctx.fillStyle = dark ? 'rgba(246,241,232,0.6)' : C.muted;
    ctx.font = `500 24px ${MONO}`;
    ctx.textAlign = 'right';
    ctx.fillText('treinprikker.nl', w - 72, 94);
    ctx.textAlign = 'left';
    ctx.textBaseline = 'alphabetic';
}

// The pin-with-train logo, drawn with paths (same geometry as public/logo.svg, 100x110 units).
function drawLogo(ctx, x, y, height, color) {
    const s = height / 110;
    ctx.save();
    ctx.translate(x, y);
    ctx.scale(s, s);
    const pin = new Path2D('M50 108C50 108 14 66 14 40A36 36 0 0 1 86 40C86 66 50 108 50 108Z');
    ctx.fillStyle = color;
    ctx.fill(pin);
    ctx.fillStyle = '#fff';
    ctx.fill(new Path2D('M34 40a16 16 0 0 1 32 0v14a2.5 2.5 0 0 1-2.5 2.5h-27A2.5 2.5 0 0 1 34 54Z'));
    ctx.fillStyle = color;
    roundRect(ctx, 38.5, 30.5, 23, 14, 3.5);
    ctx.fill();
    ctx.beginPath();
    ctx.arc(41.5, 50, 2.8, 0, Math.PI * 2);
    ctx.arc(58.5, 50, 2.8, 0, Math.PI * 2);
    ctx.fill();
    ctx.strokeStyle = '#fff';
    ctx.lineCap = 'round';
    ctx.lineWidth = 3.2;
    ctx.stroke(new Path2D('M46 60L36.5 88M54 60L63.5 88'));
    ctx.lineWidth = 2.8;
    ctx.stroke(new Path2D('M45 66h10M43.3 72.5h13.4M41.4 79h17.2M39.5 85h21'));
    ctx.restore();
}

function roundRect(ctx, x, y, w, h, r) {
    ctx.beginPath();
    ctx.moveTo(x + r, y);
    ctx.arcTo(x + w, y, x + w, y + h, r);
    ctx.arcTo(x + w, y + h, x, y + h, r);
    ctx.arcTo(x, y + h, x, y, r);
    ctx.arcTo(x, y, x + w, y, r);
    ctx.closePath();
}

function tracked(ctx, text, x, y, spacing) {
    for (const ch of text) {
        ctx.fillText(ch, x, y);
        x += ctx.measureText(ch).width + spacing;
    }
}

function wrap(ctx, text, maxWidth) {
    const words = text.split(' ');
    const lines = [];
    let line = '';
    for (const word of words) {
        const test = line ? `${line} ${word}` : word;
        if (ctx.measureText(test).width > maxWidth && line) {
            lines.push(line);
            line = word;
        } else {
            line = test;
        }
    }
    if (line) lines.push(line);
    return lines;
}

function fit(ctx, text, maxWidth) {
    if (ctx.measureText(text).width <= maxWidth) return text;
    let t = text;
    while (t.length > 1 && ctx.measureText(t + '…').width > maxWidth) t = t.slice(0, -1);
    return t.trimEnd() + '…';
}

async function drawNetherlands(ctx, url, box, { fill, stroke, rails, pinAt }) {
    const data = await outline(url);
    if (!data) return null;
    const [west, south, east, north] = data.bbox;
    const cos = Math.cos(((south + north) / 2) * (Math.PI / 180));
    const scale = Math.min(box.w / ((east - west) * cos), box.h / (north - south));
    const width = (east - west) * cos * scale;
    const height = (north - south) * scale;
    const ox = box.x + (box.w - width) / 2;
    const oy = box.y + (box.h - height) / 2;
    const project = ([lng, lat]) => [ox + (lng - west) * cos * scale, oy + (north - lat) * scale];

    ctx.save();
    ctx.beginPath();
    for (const ring of data.polygons) {
        ring.forEach((p, i) => {
            const [x, y] = project(p);
            i === 0 ? ctx.moveTo(x, y) : ctx.lineTo(x, y);
        });
        ctx.closePath();
    }
    ctx.fillStyle = fill;
    ctx.fill();
    if (stroke) {
        ctx.strokeStyle = stroke;
        ctx.lineWidth = 1.2;
        ctx.stroke();
    }
    if (rails) {
        ctx.strokeStyle = rails;
        ctx.lineWidth = 1.6;
        ctx.lineJoin = ctx.lineCap = 'round';
        ctx.beginPath();
        for (const line of data.rail ?? []) {
            line.forEach((p, i) => {
                const [x, y] = project(p);
                i === 0 ? ctx.moveTo(x, y) : ctx.lineTo(x, y);
            });
        }
        ctx.stroke();
    }
    ctx.restore();
    return pinAt ? project(pinAt) : null;
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
    ctx.fillStyle = C.signal;
    ctx.fill();
    ctx.lineWidth = 3;
    ctx.strokeStyle = C.ink;
    ctx.stroke();
    ctx.beginPath();
    ctx.arc(0, -12, 7, 0, Math.PI * 2);
    ctx.fillStyle = C.ink;
    ctx.fill();
    ctx.restore();
}

function toBlob(el) {
    return new Promise((resolve) => el.toBlob(resolve, 'image/png'));
}

/**
 * Weekly ranking: five stations with their median distance.
 */
export async function renderRanking({ title, eyebrow, footer, rows }) {
    const [el, ctx] = canvas(1080, 1080);
    ctx.fillStyle = C.paper;
    ctx.fillRect(0, 0, 1080, 1080);
    brandBar(ctx, 1080, false);

    ctx.fillStyle = C.muted;
    ctx.font = `600 30px ${SANS}`;
    tracked(ctx, eyebrow.toUpperCase(), 72, 200, 4);
    ctx.fillStyle = C.ink;
    ctx.font = `800 68px ${SANS}`;
    ctx.fillText(fit(ctx, title, 936), 72, 280);

    const top = 330;
    const rowH = 118;
    roundRect(ctx, 72, top, 936, rowH * rows.length, 24);
    ctx.fillStyle = C.board;
    ctx.fill();

    rows.forEach((row, i) => {
        const y = top + i * rowH;
        if (i > 0) {
            ctx.strokeStyle = 'rgba(246,241,232,0.14)';
            ctx.lineWidth = 1;
            ctx.beginPath();
            ctx.moveTo(72, y);
            ctx.lineTo(1008, y);
            ctx.stroke();
        }
        ctx.textBaseline = 'middle';
        ctx.fillStyle = 'rgba(246,241,232,0.55)';
        ctx.font = `500 34px ${MONO}`;
        ctx.fillText(String(i + 1), 108, y + rowH / 2);
        ctx.fillStyle = C.paper;
        ctx.font = `700 38px ${SANS}`;
        ctx.fillText(fit(ctx, row.name, 520), 172, y + rowH / 2 - 16);
        ctx.fillStyle = 'rgba(246,241,232,0.6)';
        ctx.font = `500 22px ${SANS}`;
        ctx.fillText(row.province, 172, y + rowH / 2 + 22);
        ctx.fillStyle = C.signal;
        ctx.font = `600 40px ${MONO}`;
        ctx.textAlign = 'right';
        ctx.fillText(row.distance, 972, y + rowH / 2);
        ctx.textAlign = 'left';
        ctx.textBaseline = 'alphabetic';
    });

    ctx.fillStyle = C.muted;
    ctx.font = `500 24px ${SANS}`;
    ctx.fillText(footer, 72, top + rowH * rows.length + 56);

    return toBlob(el);
}

/**
 * The three "zo werkt het" carousel slides (4:5).
 */
export async function renderSlides(outlineUrl) {
    const slides = [];

    // 1. Five stations a day (dark).
    {
        const [el, ctx] = canvas(1080, 1350);
        ctx.fillStyle = C.board;
        ctx.fillRect(0, 0, 1080, 1350);
        brandBar(ctx, 1080, true);
        const colors = ['#2f8f5b', '#c98700', '#2f8f5b', '#c9432f', '#d96b1a'];
        const startX = 540 - (4 * 134) / 2;
        ctx.strokeStyle = 'rgba(246,241,232,0.25)';
        ctx.lineWidth = 6;
        ctx.beginPath();
        ctx.moveTo(startX, 560);
        ctx.lineTo(startX + 4 * 134, 560);
        ctx.stroke();
        colors.forEach((color, i) => {
            const x = startX + i * 134;
            ctx.beginPath();
            ctx.arc(x, 560, 34, 0, Math.PI * 2);
            ctx.fillStyle = 'rgba(246,241,232,0.12)';
            ctx.fill();
            ctx.beginPath();
            ctx.arc(x, 560, 26, 0, Math.PI * 2);
            ctx.fillStyle = color;
            ctx.fill();
        });
        slideText(ctx, true, '1 / 3', 'Elke dag vijf stations.', 'Iedereen krijgt dezelfde vijf. Om middernacht staat de volgende klaar.');
        slides.push(await toBlob(el));
    }

    // 2. Pin on the map.
    {
        const [el, ctx] = canvas(1080, 1350);
        ctx.fillStyle = C.paper;
        ctx.fillRect(0, 0, 1080, 1350);
        brandBar(ctx, 1080, false);
        const at = await drawNetherlands(ctx, outlineUrl, { x: 280, y: 200, w: 520, h: 600 }, { fill: C.paperDeep, stroke: '#a39a8e', rails: '#4a4540', pinAt: [5.12, 52.09] });
        if (at) drawPin(ctx, at[0], at[1] - 2, 1.5);
        slideText(ctx, false, '2 / 3', 'Prik waar jij denkt dat het ligt.', 'Alleen de naam. Geen hints, geen steden op de kaart.');
        slides.push(await toBlob(el));
    }

    // 3. Points.
    {
        const [el, ctx] = canvas(1080, 1350);
        ctx.fillStyle = C.paper;
        ctx.fillRect(0, 0, 1080, 1350);
        brandBar(ctx, 1080, false);
        const chips = [
            ['994', 'op 1 km', '#dff1e6', '#1f6b41'],
            ['740', 'op 25 km', '#fbedc8', '#7a5200'],
            ['203', 'op 100 km', '#f9dcd7', '#8e2c1f'],
        ];
        const chipW = 250;
        const gap = 24;
        const startX = 540 - (3 * chipW + 2 * gap) / 2;
        chips.forEach(([points, label, bg, fg], i) => {
            const x = startX + i * (chipW + gap);
            roundRect(ctx, x, 440, chipW, 200, 24);
            ctx.fillStyle = bg;
            ctx.fill();
            ctx.textAlign = 'center';
            ctx.fillStyle = fg;
            ctx.font = `600 72px ${MONO}`;
            ctx.fillText(points, x + chipW / 2, 540);
            ctx.font = `600 26px ${SANS}`;
            ctx.fillText(label, x + chipW / 2, 590);
            ctx.textAlign = 'left';
        });
        slideText(ctx, false, '3 / 3', 'Hoe dichterbij, hoe meer punten.', 'Maximaal 1000 per station, 5000 per dag. Deel je score en bouw een streak.');
        slides.push(await toBlob(el));
    }

    return slides;
}

function slideText(ctx, dark, counter, title, body) {
    ctx.fillStyle = dark ? 'rgba(246,241,232,0.6)' : C.muted;
    ctx.font = `500 28px ${MONO}`;
    ctx.fillText(counter, 72, 920);
    ctx.fillStyle = dark ? C.paper : C.ink;
    ctx.font = `800 76px ${SANS}`;
    let y = 1010;
    for (const line of wrap(ctx, title, 936)) {
        ctx.fillText(line, 72, y);
        y += 82;
    }
    ctx.fillStyle = dark ? 'rgba(246,241,232,0.75)' : C.muted;
    ctx.font = `500 32px ${SANS}`;
    y += 8;
    for (const line of wrap(ctx, body, 860)) {
        ctx.fillText(line, 72, y);
        y += 44;
    }
}
