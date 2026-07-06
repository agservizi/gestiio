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

            window.initGestiioDropzone = window.initGestiioDropzone || function (selector, options) {
                if (!window.Dropzone || !document.querySelector(selector)) {
                    return null;
                }

                var csrfToken = options.csrfToken || $('meta[name="csrf-token"]').attr('content') || $('meta[name="_token"]').attr('content') || '';
                var existingFiles = options.existingFiles || [];
                var sendingData = options.sendingData || {};

                return new Dropzone(selector, {
                    url: options.uploadUrl,
                    paramName: 'file',
                    maxFiles: options.maxFiles || 10,
                    maxFilesize: options.maxFilesize || 20,
                    addRemoveLinks: typeof options.addRemoveLinks === 'boolean' ? options.addRemoveLinks : true,
                    dictRemoveFile: 'Rimuovi',
                    dictCancelUpload: 'Annulla',
                    dictFileTooBig: 'File troppo grande. Limite: {{filesize}} MB.',
                    dictMaxFilesExceeded: 'Hai raggiunto il numero massimo di file.',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken
                    },
                    init: function () {
                        var dz = this;

                        dz.on('sending', function (file, xhr, formData) {
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

                            dz.emit('addedfile', mockFile);
                            if (value.thumbnail) {
                                dz.emit('thumbnail', mockFile, '/storage/' + value.thumbnail);
                            }
                            dz.emit('complete', mockFile);
                            dz.files.push(mockFile);
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
                    error: function (file, message) {
                        var text = (typeof message === 'string') ? message : (message && message.message ? message.message : 'Upload non riuscito.');
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
            };
        </script>
    @endpush
@endonce
