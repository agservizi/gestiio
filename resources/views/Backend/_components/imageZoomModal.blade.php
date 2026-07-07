@once
    <div class="modal fade" id="gestiioImageZoomModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content bg-transparent border-0">
                <button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 m-4" data-bs-dismiss="modal" aria-label="Chiudi"></button>
                <div class="modal-body p-0 text-center">
                    <img id="gestiioImageZoomModalImg" src="" class="w-100 rounded shadow" alt="">
                </div>
            </div>
        </div>
    </div>

    <style>
        .zoomable-thumb {
            cursor: zoom-in;
        }
    </style>

    @push('customScript')
        <script>
            document.addEventListener('click', function (e) {
                var thumb = e.target.closest('.zoomable-thumb');
                if (!thumb) {
                    return;
                }

                document.getElementById('gestiioImageZoomModalImg').src = thumb.getAttribute('src');
                var modalEl = document.getElementById('gestiioImageZoomModal');
                if (window.bootstrap && bootstrap.Modal) {
                    bootstrap.Modal.getOrCreateInstance(modalEl).show();
                } else {
                    $(modalEl).modal('show');
                }
            });
        </script>
    @endpush
@endonce
