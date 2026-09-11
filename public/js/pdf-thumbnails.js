(function () {
    function renderPdfThumbnails() {
        if (typeof pdfjsLib === 'undefined') {
            return;
        }

        document.querySelectorAll('[data-pdf-thumb]').forEach(function (container) {
            var url = container.dataset.pdfThumb;
            var displaySize = container.offsetWidth || 44;
            var dpr = window.devicePixelRatio || 1;

            pdfjsLib.getDocument(url).promise
                .then(function (pdf) {
                    return pdf.getPage(1);
                })
                .then(function (page) {
                    var baseViewport = page.getViewport({ scale: 1 });
                    var scale = (displaySize / Math.min(baseViewport.width, baseViewport.height)) * dpr;
                    var viewport = page.getViewport({ scale: scale });

                    var canvas = document.createElement('canvas');
                    canvas.className = 'document-card-thumb';
                    canvas.width = viewport.width;
                    canvas.height = viewport.height;

                    return page.render({ canvasContext: canvas.getContext('2d'), viewport: viewport }).promise
                        .then(function () {
                            container.innerHTML = '';
                            container.appendChild(canvas);
                        });
                })
                .catch(function () {
                    // Rendu impossible (fichier corrompu, etc.) — l'icône de repli reste affichée.
                });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', renderPdfThumbnails);
    } else {
        renderPdfThumbnails();
    }
})();
