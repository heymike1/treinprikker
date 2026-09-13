import { renderShareImage } from './share-image';

/**
 * Result sharing: the card is rendered as soon as the summary shows. The
 * share button opens the native share sheet with image + text where the
 * browser has one, otherwise it copies the image (or downloads it).
 */
export default function shareResult({ text, image }) {
    let blob = null;

    return {
        text,
        feedback: null,
        timer: null,
        busy: false,
        imageUrl: null,
        imageFailed: false,

        async init() {
            try {
                blob = await renderShareImage(image);
                this.imageUrl = URL.createObjectURL(blob);
            } catch (error) {
                console.error(error);
                this.imageFailed = true;
            }
        },

        get canCopyImage() {
            return typeof ClipboardItem !== 'undefined' && !!navigator.clipboard?.write;
        },

        async share() {
            if (this.busy) {
                return;
            }
            this.busy = true;
            try {
                if (blob) {
                    const file = new File([blob], 'treinprikker.png', { type: 'image/png' });
                    if (navigator.share && navigator.canShare?.({ files: [file] })) {
                        try {
                            await navigator.share({ files: [file], text: this.text });
                            this.$wire?.shareClicked('native');
                            return;
                        } catch (error) {
                            if (error?.name === 'AbortError') {
                                return;
                            }
                        }
                    }

                    if (this.canCopyImage) {
                        await this.copyImage();
                        return;
                    }

                    await this.saveImage();
                    return;
                }

                await this.copyText();
            } finally {
                this.busy = false;
            }
        },

        async copyImage() {
            try {
                await navigator.clipboard.write([new ClipboardItem({ 'image/png': blob })]);
                this.flash('Afbeelding gekopieerd');
                this.$wire?.shareClicked('clipboard');
            } catch {
                await this.saveImage();
            }
        },

        get isAppleTouch() {
            return /iPad|iPhone|iPod/.test(navigator.userAgent)
                || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
        },

        /**
         * "Download" means different things per platform: a real download on
         * desktop and Android, the share sheet (with "Bewaar afbeelding") on
         * iOS where downloads land in the Files app, and a plain image tab
         * where neither works (in-app browsers).
         */
        async download() {
            if (!this.imageUrl || this.busy) {
                return;
            }
            this.busy = true;
            try {
                await this.saveImage();
            } finally {
                this.busy = false;
            }
        },

        async saveImage() {
            const file = new File([blob], 'treinprikker.png', { type: 'image/png' });
            if (this.isAppleTouch && navigator.share && navigator.canShare?.({ files: [file] })) {
                try {
                    await navigator.share({ files: [file] });
                    this.$wire?.shareClicked('download');
                    return;
                } catch (error) {
                    if (error?.name === 'AbortError') {
                        return;
                    }
                }
            }

            if ('download' in HTMLAnchorElement.prototype && !this.isAppleTouch) {
                const link = document.createElement('a');
                link.href = this.imageUrl;
                link.download = 'treinprikker.png';
                document.body.appendChild(link);
                link.click();
                link.remove();
                this.flash('Afbeelding gedownload');
                this.$wire?.shareClicked('download');
                return;
            }

            window.open(this.imageUrl, '_blank');
            this.flash('Houd de afbeelding ingedrukt om op te slaan');
            this.$wire?.shareClicked('download');
        },

        async copyText() {
            try {
                await this.copy();
                this.flash('Tekst gekopieerd');
                this.$wire?.shareClicked('clipboard');
            } catch {
                this.flash('Kopiëren lukte niet. Selecteer de tekst hieronder.');
            }
        },

        async copy() {
            if (navigator.clipboard?.writeText) {
                try {
                    await navigator.clipboard.writeText(this.text);
                    return;
                } catch {
                    // Permission denied or insecure context: fall through to the legacy path.
                }
            }
            const area = document.createElement('textarea');
            area.value = this.text;
            area.setAttribute('readonly', '');
            area.style.position = 'absolute';
            area.style.left = '-9999px';
            document.body.appendChild(area);
            area.select();
            const copied = document.execCommand('copy');
            document.body.removeChild(area);
            if (!copied) {
                throw new Error('copy failed');
            }
        },

        flash(message) {
            this.feedback = message;
            clearTimeout(this.timer);
            this.timer = setTimeout(() => (this.feedback = null), 3500);
        },
    };
}
