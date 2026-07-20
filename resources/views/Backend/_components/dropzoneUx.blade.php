@once
    @push('customCss')
        <style>
            .gestiio-dropzone {
                min-height: 174px;
                border: 1px dashed #93c5fd;
                border-radius: 8px;
                background: #f8fbff;
                transition: border-color .18s ease, background-color .18s ease, box-shadow .18s ease;
            }

            .gestiio-dropzone.dz-drag-hover {
                border-color: #0ea5e9;
                background: #eef6ff;
                box-shadow: 0 12px 28px rgba(14, 165, 233, .12);
            }

            .gestiio-dropzone .dz-message {
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 16px;
                min-height: 142px;
                margin: 0;
                padding: 20px;
                text-align: left;
            }

            .gestiio-dropzone-icon {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                width: 54px;
                height: 54px;
                flex: 0 0 54px;
                border-radius: 8px;
                background: #e0f2fe;
                color: #0369a1;
                font-size: 25px;
            }

            .gestiio-dropzone h3 {
                margin: 0 0 6px;
                color: #020617;
                font-size: 16px;
                font-weight: 800;
                letter-spacing: 0;
            }

            .gestiio-dropzone span {
                color: #64748b;
                font-size: 13px;
                font-weight: 600;
                line-height: 1.45;
            }

            .gestiio-dropzone .dz-preview {
                border-radius: 8px;
            }

            .gestiio-dropzone .dz-error-message {
                top: 150px;
            }

            .gestiio-mobile-scan-actions {
                margin-top: 12px;
                display: flex;
                align-items: center;
                gap: 10px;
            }

            .gestiio-stirling-scan-btn {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                width: 42px;
                height: 42px;
                padding: 0;
                border: 1px solid #e5e7eb;
                border-radius: 10px;
                background: #fff;
                box-shadow: 0 1px 2px rgba(15, 23, 42, .04);
                cursor: pointer;
                transition: border-color .15s ease, box-shadow .15s ease;
            }

            .gestiio-stirling-scan-btn:hover {
                border-color: #93c5fd;
                box-shadow: 0 4px 12px rgba(59, 130, 246, .12);
            }

            .gestiio-stirling-scan-btn svg {
                width: 22px;
                height: 22px;
                display: block;
            }

            .gestiio-stirling-scan-label {
                font-size: 13px;
                font-weight: 600;
                color: #64748b;
            }

            .gestiio-mobile-scan-qr {
                display: flex;
                justify-content: center;
                padding: 16px;
                background: #fff;
            }

            .gestiio-mobile-scan-qr svg {
                width: 240px;
                height: 240px;
                max-width: 100%;
            }

            #gestiioMobileScanModal .modal-content {
                border: 0;
                border-radius: 12px;
                overflow: hidden;
            }

            #gestiioMobileScanModal .modal-header {
                border-bottom: 1px solid #eef2f7;
                padding: 1rem 1.25rem;
            }

            #gestiioMobileScanModal .modal-title {
                font-size: 1.05rem;
                font-weight: 700;
                color: #0f172a;
            }

            #gestiioMobileScanModal .stirling-scan-info {
                display: flex;
                gap: 12px;
                align-items: flex-start;
                padding: 12px 14px;
                border-radius: 10px;
                background: #eff6ff;
                border: 1px solid #bfdbfe;
                color: #1e3a8a;
                font-size: 13px;
                font-weight: 600;
                line-height: 1.45;
            }

            #gestiioMobileScanModal .stirling-scan-info i {
                color: #2563eb;
                margin-top: 2px;
            }

            #gestiioMobileScanModal .stirling-scan-hint {
                text-align: center;
                color: #334155;
                font-size: 14px;
                font-weight: 600;
                margin: 0 0 10px;
            }

            #gestiioMobileScanUrl {
                display: block;
                text-align: center;
                font-size: 12px;
                color: #64748b;
                word-break: break-all;
            }

            .gestiio-mobile-scan-preview {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
                gap: 12px;
                margin-top: 16px;
            }

            .gestiio-mobile-scan-card {
                border: 1px solid #e2e8f0;
                border-radius: 8px;
                overflow: hidden;
                background: #fff;
            }

            .gestiio-mobile-scan-card iframe,
            .gestiio-mobile-scan-card img {
                display: block;
                width: 100%;
                height: 120px;
                object-fit: contain;
                background: #f8fafc;
                border: 0;
            }

            .gestiio-mobile-scan-card .meta {
                padding: 8px 10px;
                font-size: 12px;
                font-weight: 600;
                color: #334155;
                word-break: break-word;
            }

            @media (max-width: 575.98px) {
                .gestiio-dropzone .dz-message {
                    flex-direction: column;
                    text-align: center;
                }
            }
        </style>
    @endpush

    @push('customScript')
        <script>
            window.gestiioToast = window.gestiioToast || function (type, message, title) {
                var text = message || 'Operazione completata.';
                var heading = title || (type === 'error' ? 'Errore' : 'Fatto');

                if (window.toastr && typeof toastr[type] === 'function') {
                    toastr[type](text, heading);
                    return;
                }

                if (window.Swal) {
                    Swal.fire({
                        title: heading,
                        text: text,
                        icon: type === 'error' ? 'error' : (type === 'warning' ? 'warning' : 'success'),
                        buttonsStyling: false,
                        confirmButtonText: 'Ok',
                        customClass: {
                            confirmButton: 'btn btn-primary'
                        }
                    });
                }
            };

            window.gestiioMobileScan = window.gestiioMobileScan || {
                endpoints: {
                    create: @json(url('/backend/allegati-mobile-scan/session')),
                    status: @json(url('/backend/allegati-mobile-scan/session')),
                    destroy: @json(url('/backend/allegati-mobile-scan/session'))
                },
                pollTimer: null,
                sessionId: null,
                files: [],
                activeOptions: null,
                activeDz: null,
                modalReady: false,
                csrf: function () {
                    return $('meta[name="csrf-token"]').attr('content') || $('meta[name="_token"]').attr('content') || '';
                },
                stirlingIconSvg: function () {
                    return '' +
                        '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true">' +
                        '<g fill="none" stroke="#5BA3E0" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">' +
                        '<rect x="2.8" y="2.8" width="8" height="8" rx="1.4"/>' +
                        '<rect x="13.2" y="2.8" width="8" height="8" rx="1.4"/>' +
                        '<rect x="2.8" y="13.2" width="8" height="8" rx="1.4"/>' +
                        '<rect x="13.2" y="13.2" width="8" height="8" rx="1.4"/>' +
                        '<path d="M14.6 20.2 L20.2 14.6"/>' +
                        '<path d="M16.4 20.2 L20.2 16.4"/>' +
                        '<path d="M18.2 20.2 L20.2 18.2"/>' +
                        '<path d="M14.6 18.4 L18.4 14.6"/>' +
                        '</g></svg>';
                },
                ensureModal: function () {
                    if (this.modalReady || document.getElementById('gestiioMobileScanModal')) {
                        this.modalReady = true;
                        return;
                    }
                    var html = '' +
                        '<div class="modal fade" id="gestiioMobileScanModal" tabindex="-1" aria-hidden="true">' +
                        '<div class="modal-dialog modal-dialog-centered modal-md"><div class="modal-content">' +
                        '<div class="modal-header"><h3 class="modal-title">Carica da dispositivo mobile</h3>' +
                        '<button type="button" class="btn btn-icon btn-sm btn-active-light-primary" data-bs-dismiss="modal" aria-label="Chiudi">' +
                        '<i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i></button></div>' +
                        '<div class="modal-body">' +
                        '<div class="stirling-scan-info mb-5"><i class="fas fa-info-circle"></i>' +
                        '<div>Scansiona per caricare foto. Le immagini vengono convertite automaticamente in PDF.</div></div>' +
                        '<div id="gestiioMobileScanQrWrap" class="gestiio-mobile-scan-qr mb-4"></div>' +
                        '<p class="stirling-scan-hint">Scansiona con la fotocamera del telefono. Le immagini vengono convertite automaticamente in PDF.</p>' +
                        '<a id="gestiioMobileScanUrl" href="#" target="_blank" rel="noopener"></a>' +
                        '<div id="gestiioMobileScanStatus" class="text-muted text-center mt-4 mb-2">In attesa della scansione…</div>' +
                        '<div id="gestiioMobileScanPreview" class="gestiio-mobile-scan-preview"></div></div>' +
                        '<div class="modal-footer">' +
                        '<button type="button" class="btn btn-light" data-bs-dismiss="modal" id="gestiioMobileScanCancel">Annulla</button>' +
                        '<button type="button" class="btn btn-primary" id="gestiioMobileScanConfirm" disabled>Conferma allegati</button>' +
                        '</div></div></div></div>';
                    document.body.insertAdjacentHTML('beforeend', html);
                    var self = this;
                    $('#gestiioMobileScanConfirm').on('click', function () {
                        self.confirm();
                    });
                    $('#gestiioMobileScanModal').on('hidden.bs.modal', function () {
                        self.closeSession();
                        $('#gestiioMobileScanConfirm').prop('disabled', true).text('Conferma allegati');
                    });
                    this.modalReady = true;
                },
                ensureButton: function (dropzoneEl, options, dz) {
                    if (options.mobileScan === false) {
                        return;
                    }
                    var $dz = $(dropzoneEl);
                    if ($dz.next('.gestiio-mobile-scan-actions').length) {
                        return;
                    }
                    var self = this;
                    var $wrap = $('<div class="gestiio-mobile-scan-actions"></div>');
                    var $btn = $('<button type="button" class="gestiio-stirling-scan-btn" title="Carica da dispositivo mobile" aria-label="Carica da dispositivo mobile"></button>');
                    $btn.html(self.stirlingIconSvg());
                    $btn.on('click', function () {
                        self.open(options, dz);
                    });
                    $wrap.append($btn);
                    $wrap.append('<span class="gestiio-stirling-scan-label">Carica da dispositivo mobile</span>');
                    $dz.after($wrap);
                },
                open: function (options, dz) {
                    var self = this;
                    self.ensureModal();
                    self.stopPoll();
                    self.activeOptions = options;
                    self.activeDz = dz;
                    self.files = [];
                    self.sessionId = null;

                    $('#gestiioMobileScanQrWrap').html('<div class="text-muted py-10">Creazione sessione…</div>');
                    $('#gestiioMobileScanUrl').text('').attr('href', '#');
                    $('#gestiioMobileScanStatus').text('In attesa della scansione…');
                    $('#gestiioMobileScanPreview').empty();
                    $('#gestiioMobileScanConfirm').prop('disabled', true);

                    var modalEl = document.getElementById('gestiioMobileScanModal');
                    if (window.bootstrap && bootstrap.Modal) {
                        bootstrap.Modal.getOrCreateInstance(modalEl).show();
                    } else {
                        $(modalEl).modal('show');
                    }

                    $.ajax({
                        url: self.endpoints.create,
                        type: 'POST',
                        dataType: 'json',
                        headers: { 'X-CSRF-TOKEN': self.csrf() }
                    }).done(function (resp) {
                        if (!resp || !resp.success) {
                            gestiioToast('error', (resp && resp.message) || 'Sessione non creata.');
                            return;
                        }
                        self.sessionId = resp.sessionId;
                        $('#gestiioMobileScanQrWrap').html(resp.qrSvg || '');
                        $('#gestiioMobileScanUrl').text(resp.scanUrl).attr('href', resp.scanUrl);
                        self.startPoll();
                    }).fail(function (xhr) {
                        var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'Scanner telefono non disponibile.';
                        gestiioToast('error', msg);
                        $('#gestiioMobileScanStatus').text(msg);
                    });
                },
                startPoll: function () {
                    var self = this;
                    self.stopPoll();
                    self.pollOnce();
                    self.pollTimer = setInterval(function () {
                        self.pollOnce();
                    }, 2500);
                },
                stopPoll: function () {
                    if (this.pollTimer) {
                        clearInterval(this.pollTimer);
                        this.pollTimer = null;
                    }
                },
                pollOnce: function () {
                    var self = this;
                    if (!self.sessionId) {
                        return;
                    }
                    $.ajax({
                        url: self.endpoints.status + '/' + encodeURIComponent(self.sessionId),
                        type: 'GET',
                        dataType: 'json'
                    }).done(function (resp) {
                        if (!resp || !resp.success) {
                            return;
                        }
                        self.files = resp.files || [];
                        self.renderPreview();
                        if (self.files.length) {
                            $('#gestiioMobileScanStatus').text(self.files.length + ' file pronti. Controlla l\'anteprima e conferma. (Sul telefono puoi chiudere la pagina.)');
                            $('#gestiioMobileScanConfirm').prop('disabled', false);
                            // File già copiati in Gestiio: stop poll (Stirling ha chiuso la sessione dopo il download).
                            if (resp.materialized) {
                                self.stopPoll();
                            }
                        } else {
                            $('#gestiioMobileScanStatus').text('In attesa della scansione…');
                            $('#gestiioMobileScanConfirm').prop('disabled', true);
                        }
                    });
                },
                renderPreview: function () {
                    var $box = $('#gestiioMobileScanPreview').empty();
                    (this.files || []).forEach(function (file) {
                        var isPdf = (file.contentType || '').indexOf('pdf') !== -1 || /\.pdf$/i.test(file.filename || '');
                        var media = isPdf
                            ? '<iframe src="' + file.previewUrl + '" title="' + (file.filename || '') + '"></iframe>'
                            : '<img src="' + file.previewUrl + '" alt="' + (file.filename || '') + '">';
                        $box.append(
                            '<div class="gestiio-mobile-scan-card">' + media +
                            '<div class="meta">' + (file.filename || 'file') +
                            (file.size ? '<br><span class="text-muted">' + Math.round(file.size / 1024) + ' KB</span>' : '') +
                            '</div></div>'
                        );
                    });
                },
                closeSession: function () {
                    var self = this;
                    self.stopPoll();
                    if (!self.sessionId) {
                        return;
                    }
                    var id = self.sessionId;
                    self.sessionId = null;
                    $.ajax({
                        url: self.endpoints.destroy + '/' + encodeURIComponent(id),
                        type: 'DELETE',
                        headers: { 'X-CSRF-TOKEN': self.csrf() }
                    });
                },
                confirm: function () {
                    var self = this;
                    var options = self.activeOptions || {};
                    var dz = self.activeDz;
                    var files = self.files || [];
                    if (!files.length || !options.uploadUrl) {
                        return;
                    }

                    $('#gestiioMobileScanConfirm').prop('disabled', true).text('Allegando…');
                    self.stopPoll();

                    var chain = $.Deferred().resolve().promise();
                    files.forEach(function (fileMeta) {
                        chain = chain.then(function () {
                            return fetch(fileMeta.previewUrl, { credentials: 'same-origin' })
                                .then(function (r) {
                                    if (!r.ok) {
                                        throw new Error('Download anteprima fallito');
                                    }
                                    return r.blob();
                                })
                                .then(function (blob) {
                                    var formData = new FormData();
                                    var name = fileMeta.filename || 'scan.pdf';
                                    formData.append('file', blob, name);
                                    var sendingData = options.sendingData || {};
                                    Object.keys(sendingData).forEach(function (key) {
                                        var val = typeof sendingData[key] === 'function' ? sendingData[key]() : sendingData[key];
                                        if (val !== undefined && val !== null) {
                                            formData.append(key, val);
                                        }
                                    });

                                    return $.ajax({
                                        url: options.uploadUrl,
                                        type: 'POST',
                                        data: formData,
                                        processData: false,
                                        contentType: false,
                                        headers: { 'X-CSRF-TOKEN': self.csrf() }
                                    }).then(function (response) {
                                        if (dz) {
                                            var mockFile = {
                                                name: name,
                                                size: fileMeta.size || blob.size || 0,
                                                filename: response.filename,
                                                id: response.id,
                                                accepted: true
                                            };
                                            dz.emit('addedfile', mockFile);
                                            if (response.thumbnail) {
                                                var thumb = response.thumbnail;
                                                if (thumb.indexOf('/') !== 0 && thumb.indexOf('http') !== 0) {
                                                    thumb = '/storage/' + thumb;
                                                }
                                                dz.emit('thumbnail', mockFile, thumb);
                                            }
                                            dz.emit('complete', mockFile);
                                            dz.files.push(mockFile);
                                            if (typeof options.onSuccess === 'function') {
                                                options.onSuccess(mockFile, response);
                                            }
                                        }
                                    });
                                });
                        });
                    });

                    chain.done(function () {
                        gestiioToast('success', 'Allegati caricati dal telefono.');
                        self.closeSession();
                        var modalEl = document.getElementById('gestiioMobileScanModal');
                        if (window.bootstrap && bootstrap.Modal) {
                            bootstrap.Modal.getOrCreateInstance(modalEl).hide();
                        } else {
                            $(modalEl).modal('hide');
                        }
                    }).fail(function () {
                        gestiioToast('error', 'Conferma allegati non riuscita.');
                        $('#gestiioMobileScanConfirm').prop('disabled', false);
                    }).always(function () {
                        $('#gestiioMobileScanConfirm').text('Conferma allegati');
                    });
                }
            };

            window.initGestiioDropzone = window.initGestiioDropzone || function (selector, options) {
                if (!window.Dropzone || !document.querySelector(selector)) {
                    return null;
                }

                var csrfToken = options.csrfToken || $('meta[name="csrf-token"]').attr('content') || $('meta[name="_token"]').attr('content') || '';
                var existingFiles = options.existingFiles || [];
                var sendingData = options.sendingData || {};
                var dropzoneEl = document.querySelector(selector);

                var dz = new Dropzone(selector, {
                    url: options.uploadUrl,
                    paramName: 'file',
                    maxFiles: options.maxFiles || 10,
                    maxFilesize: options.maxFilesize || 20,
                    addRemoveLinks: typeof options.addRemoveLinks === 'boolean' ? options.addRemoveLinks : true,
                    dictRemoveFile: 'Rimuovi',
                    dictCancelUpload: 'Annulla',
                    dictFileTooBig: 'File troppo grande. Limite: @{{filesize}} MB.',
                    dictMaxFilesExceeded: 'Hai raggiunto il numero massimo di file.',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken
                    },
                    init: function () {
                        var instance = this;

                        instance.on('sending', function (file, xhr, formData) {
                            Object.keys(sendingData).forEach(function (key) {
                                formData.append(key, typeof sendingData[key] === 'function' ? sendingData[key]() : sendingData[key]);
                            });
                        });

                        existingFiles.forEach(function (value) {
                            var mockFile = {
                                name: value.filename_originale || value.path_filename || value.filename || 'allegato',
                                size: value.dimensione_file || 0,
                                filename: value.path_filename || value.filename,
                                id: value.id,
                                accepted: true
                            };

                            instance.emit('addedfile', mockFile);
                            if (value.thumbnail) {
                                instance.emit('thumbnail', mockFile, '/storage/' + value.thumbnail);
                            }
                            instance.emit('complete', mockFile);
                            instance.files.push(mockFile);
                        });
                    },
                    accept: function (file, done) {
                        done();
                    },
                    success: function (file, response) {
                        file.filename = response.filename;
                        file.id = response.id;
                        if (response.thumbnail && file.previewElement && file.previewElement.querySelector('img')) {
                            file.previewElement.querySelector('img').src = response.thumbnail;
                        }
                        if (typeof options.onSuccess === 'function') {
                            options.onSuccess(file, response);
                        }
                    },
                    error: function (file, message, xhr) {
                        var text = 'Upload non riuscito.';
                        if (typeof message === 'string' && message && message !== 'Server Error') {
                            text = message;
                        } else if (message && message.message) {
                            text = message.message;
                        } else if (xhr && xhr.responseText) {
                            try {
                                var parsed = JSON.parse(xhr.responseText);
                                if (parsed && parsed.message) {
                                    text = parsed.message;
                                }
                            } catch (e) {}
                        }
                        if (file.previewElement) {
                            file.previewElement.classList.add('dz-error');
                            var errorNode = file.previewElement.querySelector('[data-dz-errormessage]');
                            if (errorNode) {
                                errorNode.textContent = text;
                            }
                        }
                        if (window.gestiioToast) {
                            gestiioToast('error', text, 'Upload non riuscito');
                        }
                        if (typeof options.onError === 'function') {
                            options.onError(file, message);
                        }
                    },
                    removedfile: function (file) {
                        var fileRef = file.previewElement;

                        if (!file.id) {
                            if (typeof options.onRemoved === 'function') {
                                options.onRemoved(file, null);
                            }
                            return fileRef && fileRef.parentNode ? fileRef.parentNode.removeChild(fileRef) : void 0;
                        }

                        $.ajax({
                            headers: {
                                'X-CSRF-TOKEN': csrfToken
                            },
                            type: 'DELETE',
                            url: options.deleteUrl,
                            data: {
                                id: file.id
                            },
                            success: function (data) {
                                if (typeof options.onRemoved === 'function') {
                                    options.onRemoved(file, data);
                                }
                            },
                            error: function (xhr) {
                                var text = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Rimozione allegato non riuscita.';
                                if (window.gestiioToast) {
                                    gestiioToast('error', text);
                                }
                            }
                        });

                        return fileRef && fileRef.parentNode ? fileRef.parentNode.removeChild(fileRef) : void 0;
                    }
                });

                window.gestiioMobileScan.ensureButton(dropzoneEl, options, dz);

                return dz;
            };
        </script>
    @endpush
@endonce
