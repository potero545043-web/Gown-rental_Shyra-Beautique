import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

document.addEventListener('DOMContentLoaded', () => {
    const logoutUrl = document.querySelector('meta[name="auth-logout-url"]')?.content;
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    let navigatingWithinSite = false;
    let skipNextBeforeUnload = false;

    if (logoutUrl && csrfToken) {
        window.__sbMarkInternalNavigation = () => {
            navigatingWithinSite = true;
        };

        const interactiveSelector = 'a, button, details, form, input, select, summary, textarea';

        const navigateToRow = row => {
            const destination = new URL(row.dataset.href, window.location.href);
            if (destination.origin !== window.location.origin) return;

            navigatingWithinSite = true;
            window.location.assign(destination.href);
        };

        document.addEventListener('click', async event => {
            if (!(event.target instanceof Element)) return;
            if (event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;

            const row = event.target.closest('tr.sb-gown-row-link[data-href], tr.sb-clickable-row[data-href]');
            if (row && !event.target.closest(interactiveSelector)) {
                navigateToRow(row);
                return;
            }

            const link = event.target.closest('a[href]');
            if (!(link instanceof HTMLAnchorElement) || link.hasAttribute('download')) return;

            const destination = new URL(link.href, window.location.href);
            if (!['http:', 'https:'].includes(destination.protocol)
                || destination.origin === window.location.origin) {
                if (destination.origin === window.location.origin
                    && link.target !== '_blank') {
                    navigatingWithinSite = true;
                }
                return;
            }

            event.preventDefault();

            if (!window.confirm('You are leaving Shyra Beautique. Continue and log out?')) return;

            const newTab = link.target === '_blank' ? window.open('about:blank', '_blank') : null;
            if (newTab) newTab.opener = null;

            try {
                const response = await fetch(logoutUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                });

                if (!response.ok) {
                    throw new Error(`Logout request failed with status ${response.status}.`);
                }

                if (newTab) {
                    newTab.location.href = destination.href;
                    navigatingWithinSite = true;
                    window.location.replace('/login');
                } else {
                    skipNextBeforeUnload = true;
                    window.location.assign(destination.href);
                }
            } catch (error) {
                newTab?.close();
                console.error('Unable to log out before leaving the site.', error);
                window.alert('We could not log you out. Please try again before leaving this page.');
            }
        }, true);

        document.addEventListener('keydown', event => {
            if (!(event.target instanceof Element)
                || !['Enter', ' '].includes(event.key)
                || event.target.closest(interactiveSelector)) {
                return;
            }

            const row = event.target.closest('tr.sb-gown-row-link[data-href], tr.sb-clickable-row[data-href]');
            if (!row) return;

            event.preventDefault();
            navigateToRow(row);
        }, true);

        document.addEventListener('submit', event => {
            if (!(event.target instanceof HTMLFormElement)) return;

            const destination = new URL(event.target.action, window.location.href);
            if (destination.origin === window.location.origin) {
                window.__sbMarkInternalNavigation();
            }
        }, true);

        window.addEventListener('beforeunload', event => {
            if (navigatingWithinSite || skipNextBeforeUnload) return;

            event.preventDefault();
            event.returnValue = '';
        });

        window.addEventListener('pagehide', () => {
            if (navigatingWithinSite || !navigator.sendBeacon) return;

            const payload = new FormData();
            payload.append('_token', csrfToken);
            navigator.sendBeacon(logoutUrl, payload);
        });

        window.addEventListener('pageshow', event => {
            if (event.persisted) window.location.reload();
        });
    }

    document.querySelectorAll('input[type="file"]').forEach(input => {
        const label = input.closest('label');
        const existingPreview = label?.querySelector('.sb-image-preview');
        const preview = document.createElement('div');
        preview.className = 'sb-upload-preview';

        const image = existingPreview || document.createElement('img');
        image.classList.add('sb-image-preview');
        image.alt ||= 'Selected image preview';
        image.hidden = !image.getAttribute('src');
        preview.hidden = image.hidden;

        const fileName = document.createElement('span');
        fileName.className = 'sb-upload-preview-name';

        preview.append(image, fileName);
        input.insertAdjacentElement('afterend', preview);

        let previewUrl = null;
        input.addEventListener('change', () => {
            if (previewUrl) {
                URL.revokeObjectURL(previewUrl);
                previewUrl = null;
            }

            const file = input.files?.[0];
            if (!file) {
                preview.hidden = !image.getAttribute('src');
                image.hidden = !image.getAttribute('src');
                fileName.textContent = '';
                return;
            }

            preview.hidden = false;
            fileName.textContent = file.name;
            if (file.type.startsWith('image/')) {
                previewUrl = URL.createObjectURL(file);
                image.src = previewUrl;
                image.alt = `Preview of ${file.name}`;
                image.hidden = false;
                preview.classList.remove('is-not-image');
            } else {
                image.removeAttribute('src');
                image.hidden = true;
                preview.classList.add('is-not-image');
            }
        });
    });

    const publicKeyPem = document.querySelector('meta[name="login-password-public-key"]')?.content;

    const bytesToBase64 = bytes => {
        let binary = '';
        bytes.forEach(byte => (binary += String.fromCharCode(byte)));
        return btoa(binary);
    };

    const pemToBytes = pem => Uint8Array.from(
        atob(pem.replace(/-----[^-]+-----/g, '').replace(/\s/g, '')),
        character => character.charCodeAt(0),
    );

    document.addEventListener('submit', async event => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || form.method.toLowerCase() !== 'post'
            || form.dataset.passwordsEncrypted === 'true') {
            return;
        }

        const fields = [...form.querySelectorAll('input[type="password"][name]:not(:disabled)')];
        if (fields.length === 0) return;

        event.preventDefault();

        try {
            if (!publicKeyPem || !window.crypto?.subtle) {
                throw new Error('Secure password submission is unavailable.');
            }

            const rsaKey = await crypto.subtle.importKey(
                'spki',
                pemToBytes(publicKeyPem),
                { name: 'RSA-OAEP', hash: 'SHA-1' },
                false,
                ['encrypt'],
            );

            for (const field of fields) {
                const aesKeyBytes = crypto.getRandomValues(new Uint8Array(32));
                const iv = crypto.getRandomValues(new Uint8Array(12));
                const aesKey = await crypto.subtle.importKey('raw', aesKeyBytes, 'AES-GCM', false, ['encrypt']);
                const ciphertext = new Uint8Array(await crypto.subtle.encrypt(
                    { name: 'AES-GCM', iv },
                    aesKey,
                    new TextEncoder().encode(field.value),
                ));
                const encryptedKey = new Uint8Array(await crypto.subtle.encrypt(
                    { name: 'RSA-OAEP' },
                    rsaKey,
                    aesKeyBytes,
                ));
                const hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = `${field.name}_encrypted`;
                hidden.value = btoa(JSON.stringify({
                    key: bytesToBase64(encryptedKey),
                    iv: bytesToBase64(iv),
                    data: bytesToBase64(ciphertext),
                }));
                form.append(hidden);
                field.value = '';
                field.disabled = true;
            }

            form.dataset.passwordsEncrypted = 'true';
            HTMLFormElement.prototype.submit.call(form);
        } catch {
            let error = form.querySelector('[data-password-encryption-error]');

            if (!error) {
                error = document.createElement('p');
                error.dataset.passwordEncryptionError = 'true';
                error.setAttribute('role', 'alert');
                error.className = 'sb-form-errors';
                form.prepend(error);
            }

            error.textContent = 'Your password could not be secured for submission. Refresh the page and try again.';
        }
    }, true);
});

/**
 * Live search + filters.
 *
 * Any GET form marked data-live-filter auto-submits as the user acts:
 *   - a text/search input submits while typing (debounced so we do not spam)
 *   - a select/date/checkbox submits immediately on change
 *
 * This is what makes typing "lavender" filter the list instantly, without
 * pressing Search. The Search button still works as a manual fallback.
 */
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('form[data-live-filter]').forEach(form => {
        let timer = null;
        let composing = false;

        const submitNow = () => form.requestSubmit ? form.requestSubmit() : form.submit();

        const debouncedSubmit = () => {
            clearTimeout(timer);
            timer = setTimeout(submitNow, 320);
        };

        form.querySelectorAll('input, select').forEach(field => {
            const type = (field.getAttribute('type') || '').toLowerCase();
            const isText = type === 'search' || type === 'text';

            if (isText) {
                // Do not fight IME composition (e.g. certain keyboard layouts).
                field.addEventListener('compositionstart', () => (composing = true));
                field.addEventListener('compositionend', () => {
                    composing = false;
                    debouncedSubmit();
                });
                field.addEventListener('input', () => {
                    if (!composing) debouncedSubmit();
                });
                // Pressing Enter should submit straight away.
                field.addEventListener('keydown', event => {
                    if (event.key === 'Enter') {
                        event.preventDefault();
                        clearTimeout(timer);
                        submitNow();
                    }
                });
            } else if (field.tagName === 'SELECT' || type === 'date' || type === 'checkbox' || type === 'radio') {
                field.addEventListener('change', submitNow);
            }
        });
    });
});
