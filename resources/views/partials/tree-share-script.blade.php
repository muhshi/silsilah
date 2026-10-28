<script>
    (function() {
        function registerTreeShare() {
            if (window.Alpine && !window._treeShareRegistered) {
                window._treeShareRegistered = true;
                Alpine.data('treeShare', (url, title) => ({
                    shared: false,
                    async share() {
                        const text = 'Halo! Yuk lihat diagram silsilah keluarga "' + title + '":\n\n' + url;
                        if (navigator.share) {
                            try {
                                await navigator.share({ title: title, text: text, url: url });
                                return;
                            } catch (e) {}
                        }
                        let ok = false;
                        if (navigator.clipboard && window.isSecureContext) {
                            try {
                                await navigator.clipboard.writeText(text);
                                ok = true;
                            } catch (e) {}
                        }
                        if (!ok) {
                            try {
                                const el = document.createElement('textarea');
                                el.value = text;
                                el.style.position = 'fixed';
                                el.style.left = '-999999px';
                                document.body.appendChild(el);
                                el.focus();
                                el.select();
                                document.execCommand('copy');
                                document.body.removeChild(el);
                                ok = true;
                            } catch (e) {}
                        }
                        if (ok) {
                            this.shared = true;
                            setTimeout(() => this.shared = false, 2500);
                        }
                    }
                }));
            }
        }
        if (window.Alpine) {
            registerTreeShare();
        } else {
            document.addEventListener('alpine:init', registerTreeShare);
        }
    })();
</script>
