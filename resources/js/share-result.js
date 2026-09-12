/**
 * Share button: native share sheet when available, clipboard otherwise.
 */
export default function shareResult(text) {
    return {
        text,
        feedback: null,
        timer: null,

        async share() {
            let method = 'clipboard';
            try {
                if (navigator.share) {
                    method = 'native';
                    await navigator.share({ text: this.text });
                } else {
                    await this.copy();
                    this.flash('Resultaat gekopieerd');
                }
            } catch (error) {
                if (error?.name === 'AbortError') {
                    return;
                }
                try {
                    await this.copy();
                    this.flash('Resultaat gekopieerd');
                } catch {
                    this.flash('Kopiëren lukte niet. Selecteer de tekst hieronder.');
                    return;
                }
            }
            this.$wire?.shareClicked(method);
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
