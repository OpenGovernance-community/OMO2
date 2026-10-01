(function () {
    'use strict';

    function extractFilename(response) {
        var disposition = response.headers.get('Content-Disposition') || '';
        var encodedMatch = disposition.match(/filename\*=UTF-8''([^;]+)/i);
        var plainMatch = disposition.match(/filename="([^"]+)"/i);
        if (encodedMatch && encodedMatch[1]) {
            try {
                return decodeURIComponent(encodedMatch[1]);
            } catch (error) {
                return encodedMatch[1];
            }
        }
        return plainMatch && plainMatch[1] ? plainMatch[1] : 'export.pdf';
    }

    document.querySelectorAll('[data-omo-pv-pdf-export]').forEach(function (link) {
        link.addEventListener('click', function (event) {
            if (link.getAttribute('aria-disabled') === 'true') {
                event.preventDefault();
                return;
            }

            event.preventDefault();
            var originalLabel = link.getAttribute('data-omo-pv-pdf-label') || link.textContent;
            var waitingLabel = link.getAttribute('data-omo-pv-pdf-waiting-label') || 'Veuillez patienter';
            var notice = link.getAttribute('data-omo-pv-pdf-notice') || '';
            var errorLabel = link.getAttribute('data-omo-pv-pdf-error') || 'Impossible de générer le PDF.';
            link.setAttribute('aria-disabled', 'true');
            link.setAttribute('aria-busy', 'true');
            link.textContent = waitingLabel;
            if (typeof window.commonNotify === 'function' && notice !== '') {
                window.commonNotify(notice, 'warning', {duration: 7000});
            }

            window.fetch(link.href, {credentials: 'same-origin', headers: {Accept: 'application/pdf'}})
                .then(function (response) {
                    if (!response.ok) {
                        return response.text().then(function (message) {
                            throw new Error(message || errorLabel);
                        });
                    }
                    var contentType = (response.headers.get('Content-Type') || '').toLowerCase();
                    return response.blob().then(function (blob) {
                        if (contentType.indexOf('text/plain') !== -1) {
                            return blob.text().then(function (message) {
                                throw new Error(message || errorLabel);
                            });
                        }
                        return {blob: blob, filename: extractFilename(response)};
                    });
                })
                .then(function (result) {
                    var blobUrl = window.URL.createObjectURL(result.blob);
                    var downloadLink = document.createElement('a');
                    downloadLink.href = blobUrl;
                    downloadLink.download = result.filename;
                    document.body.appendChild(downloadLink);
                    downloadLink.click();
                    downloadLink.remove();
                    var exportMenu = link.closest('.omo-document-detail__more-actions');
                    if (exportMenu) exportMenu.removeAttribute('open');
                    window.setTimeout(function () { window.URL.revokeObjectURL(blobUrl); }, 1000);
                })
                .catch(function (error) {
                    if (typeof window.commonNotify === 'function') {
                        window.commonNotify(error.message || errorLabel, 'error');
                    }
                })
                .finally(function () {
                    link.removeAttribute('aria-disabled');
                    link.removeAttribute('aria-busy');
                    link.textContent = originalLabel;
                });
        });
    });
}());
