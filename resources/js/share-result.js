import { renderShareImage } from './share-image';

/**
 * Share button: renders the result card, then the native share sheet with
 * image + text where available; otherwise a panel with copy buttons.
 */
export default function shareResult({ text, image }) {
    return {
        text,
        feedback: null,
        timer: null,
        busy: false,
        panelOpen: false,
        imageUrl: null,
        canCopyImage: typeof ClipboardItem !== 'undefined' && !!navigator.clipboard?.write,

        async share() {
            if (this.busy) {
                return;
            }
            this.busy = true;
            try {
                const blob = await this.imageBlob();
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

                this.openPanel(blob);
            } catch (error) {
                console.error(error);
                this.openPanel(null);
            } finally {
                this.busy = false;
            }
        },

        openPanel(blob) {
            if (blob) {
                this.imageUrl = URL.createObjectURL(blob);
                this._blob = blob;
            }
            this.panelOpen = true;
        },

        closePanel() {
            this.panelOpen = false;
        },

        async imageBlob() {
            if (!this._blob) {
                this._blob = await renderShareImage(image);
            }
            return this._blob;
        },

        async copyImage() {
            try {
                const blob = await this.imageBlob();
                await navigator.clipboard.write([new ClipboardItem({ 'image/png': blob })]);
                this.flash('Afbeelding gekopieerd');
                this.$wire?.shareClicked('clipboard');
            } catch {
                this.flash('Kopiëren lukte niet. Houd de afbeelding ingedrukt om op te slaan.');
            }
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
            this.timer = setTimeout(() => (this.feedback = null), 2500);
        },
    };
}
