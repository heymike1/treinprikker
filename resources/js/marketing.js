import { renderRanking, renderSlides } from './marketing-images';

/**
 * /admin/marketing: renders the post images and offers them as downloads.
 */
export default function marketingPosts(config) {
    const urls = {};

    return {
        images: {},
        feedback: null,

        async init() {
            const jobs = [];
            if (config.hardest.length) {
                jobs.push(renderRanking(config.hardest[0]).then((blob) => this.set('hardest', blob)));
            }
            if (config.easiest.length) {
                jobs.push(renderRanking(config.easiest[0]).then((blob) => this.set('easiest', blob)));
            }
            jobs.push(renderSlides(config.outlineUrl).then((blobs) => blobs.forEach((blob, i) => this.set(`slide${i + 1}`, blob))));
            await Promise.all(jobs);
        },

        set(key, blob) {
            urls[key] = URL.createObjectURL(blob);
            this.images = { ...this.images, [key]: urls[key] };
        },

        download(key, filename) {
            const link = document.createElement('a');
            link.href = urls[key];
            link.download = filename;
            document.body.appendChild(link);
            link.click();
            link.remove();
        },

        async copy(text) {
            try {
                await navigator.clipboard.writeText(text);
                this.flash('Bijschrift gekopieerd');
            } catch {
                this.flash('Kopiëren lukte niet; selecteer de tekst handmatig.');
            }
        },

        flash(message) {
            this.feedback = message;
            setTimeout(() => (this.feedback = null), 2500);
        },
    };
}
