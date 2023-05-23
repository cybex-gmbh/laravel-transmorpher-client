<script src="{{ mix('transmorpher.js', 'vendor/transmorpher') }}"></script>
<link rel="stylesheet" href="{{ mix('transmorpher.css', 'vendor/transmorpher') }}" type="text/css"/>

<div>
    <span id="csrf" class="d-hidden">@csrf</span>
    <div class="card @if(!$isReady || $isProcessing) border-processing @endif">
        <div class="card-header">
            <div>
                <img src="{{ mix(sprintf('icons/%s.svg', $motif->getTransmorpherMedia()->type->value), 'vendor/transmorpher') }}"
                     alt="{{ $motif->getTransmorpherMedia()->type->value }}" class="icon">
                {{ $differentiator }}
            </div>
            <div class="details">
                <img role="button" src="{{ mix('icons/more-info.svg', 'vendor/transmorpher') }}" alt="More information" class="icon"
                     onclick="openMoreInformationModal('{{ $motif->getIdentifier() }}')">
            </div>
        </div>
        <div class="card-body">
             <span class="badge @if($isProcessing) badge-processing @elseif($isUploading) badge-uploading @else d-hidden @endif">
                @if($isProcessing)
                     Processing
                 @else
                     Uploading
                 @endif
            </span>
            <form method="POST" class="dropzone" id="dz-{{ $motif->getIdentifier() }}">
                <div class="media-preview">
                    <div class="error-display d-none">
                        <span class="error-message"></span>
                        <button type="button" class="btn-close" onclick="closeErrorMessage(this)">⨉</button>
                    </div>
                    <div class="media-display">
                        @if ($isImage)
                            <a class="full-size-link" target="_blank" href="{{ $motif->getUrl() }}">
                                <div class="dz-image image-transmorpher">
                                    <img data-delivery-url="{{ $motif->getDeliveryUrl() }}"
                                         data-placeholder-url="{{ $motif->getPlaceholderUrl() }}"
                                         src="{{ $motif->getUrl(['height' => 150]) }}"
                                         alt="{{ $differentiator }}"/>
                                    <img src="{{ mix('icons/enlargen.svg', 'vendor/transmorpher') }}" alt="Enlarge image" class="icon enlarge-icon">
                                </div>
                            </a>
                        @else
                            <video preload="metadata" controls class="video-transmorpher @if(!$isReady) d-none @endif">
                                <source src="{{ $isReady ? $motif->getMp4Url() : '' }}" type="video/mp4">
                                <p style="padding: 5px;">
                                    Your browser doesn't support HTML video. Here is a
                                    <a href="{{ $isReady ? $motif->getMp4Url() : '' }}">link to the video</a> instead.
                                </p>
                            </video>
                            <img data-placeholder-url="{{ $motif->getPlaceholderUrl() }}"
                                 src="{{ !$isReady ? $motif->getUrl() : '' }}"
                                 alt="{{ $differentiator }}"
                                 class="dz-image video-transmorpher @if($isReady) d-none @endif"/>
                        @endif
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div id="modal-mi-{{ $motif->getIdentifier() }}" class="modal d-none">
        <div class="card">
            <div class="card-header">
                {{ $differentiator }}
                <span class="badge @if($isProcessing) badge-processing @else d-hidden @endif">
                    Processing
                </span>
                <button class="btn-close" onclick="closeMoreInformationModal('{{ $motif->getIdentifier() }}')">⨉</button>
            </div>
            <div class="card-body">
                <div class="version-information">
                    <p class="@if(!$isProcessing) d-none @endif">Processing started <span class="processing-age"></span></p>
                    <p class="@if(!$isUploading) d-none @endif">Upload started <span class="upload-age"></span></p>
                    <p>Current version: <span class="current-version"></span></p>
                    <p class="age">uploaded <span class="current-version-age"></span></p>
                    @if(!$isImage)
                        <hr>
                        <p>Currently processed version: <span class="processed-version"></span></p>
                        <p class="age">uploaded <span class="processed-version-age"></span></p>
                    @endif
                    <div class="version-list">
                        <p>Version overview:</p>
                        <hr>
                        <ul></ul>
                    </div>
                </div>
                <div class="delete-and-error">
                    <button class="button badge-error" onclick="showDeleteModal('{{ $motif->getIdentifier() }}')">
                        Delete
                    </button>
                    <span class="error-message"></span>
                </div>
            </div>
        </div>

        <div id="delete-{{ $motif->getIdentifier() }}" class="card d-none">
            <div class="card-header">
                Are you sure you want to delete all versions of {{ $differentiator }}?
            </div>
            <div class="card-body">
                <button class="button" onclick="closeDeleteModal('{{ $motif->getIdentifier() }}')">
                    Cancel
                </button>
                <button class="button badge-error" onclick="deleteTransmorpherMedia('{{ $motif->getIdentifier() }}')">
                    Delete
                </button>
            </div>
        </div>
    </div>

    <div id="modal-uc-{{ $motif->getIdentifier() }}" class="modal d-none">
        <div class="card">
            <div class="card-header">
                @if($isImage)
                    There is currently an upload in process, do you want to overwrite it?
                @else
                    A video is currently uploading or processing, do you want to overwrite it?
                @endif
            </div>
            <div class="card-body">
                <button class="button" onclick="closeUploadConfirmModal('{{ $motif->getIdentifier() }}')">
                    Cancel
                </button>
                <button class="button badge-error">
                    Overwrite
                </button>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    Dropzone.autoDiscover = false;

    motifs['{{ $motif->getIdentifier() }}'] = {
        transmorpherMediaKey: {{ $transmorpherMediaKey }},
        csrfToken: document.querySelector('#csrf > input[name="_token"]').value,
        routes: {
            state: '{{ $stateRoute }}',
            handleUploadResponse: '{{ $handleUploadResponseRoute }}',
            getVersions: '{{ $getVersionsRoute }}',
            setVersion: '{{ $setVersionRoute }}',
            delete: '{{ $deleteRoute }}',
            getOriginal: '{{ $getOriginalRoute }}',
            uploadToken: '{{ $uploadTokenRoute }}',
            setUploadingState: '{{ $setUploadingStateRoute }}'
        },
        webUploadUrl: '{{ $motif->getWebUploadUrl() }}',
        isImage: '{{ $isImage }}'
    }

    // Start polling if the video is still processing or an upload is in process.
    if (('{{ !$isImage }}' && '{{ $isProcessing }}') || '{{ $isUploading }}') {
        startPolling('{{ $motif->getIdentifier() }}', '{{ $latestUploadToken }}');
        setAgeElement(document.querySelector('#modal-mi-{{ $motif->getIdentifier() }} .{{ $isProcessing ? 'processing' : 'upload' }}-age'), timeAgo(new Date('{{ $lastUpdated }}' * 1000)));
    }

    new Dropzone("#dz-{{$motif->getIdentifier()}}", {
        url: '{{ $motif->getWebUploadUrl() }}',
        chunking: true,
        chunkSize: {{ $motif->getChunkSize() }},
        maxFilesize: {{ $motif->getMaxFileSize() }},
        maxThumbnailFilesize: {{ $motif->getMaxFileSize() }},
        timeout: 60000,
        uploadMultiple: false,
        paramName: 'file',
        uploadToken: null,
        createImageThumbnails: false,
        init: function () {
            // Remove all other files when a new file is dropped in. Only 1 simultaneous upload is allowed.
            this.on('addedfile', function () {
                if (this.files[1] != null) {
                    this.removeFile(this.files[0]);
                }
            });

            // Gets fired when upload is starting.
            this.on('processing', function () {
                fetch(motifs['{{$motif->getIdentifier()}}'].routes.setUploadingState + `/${this.options.uploadToken}`, {
                    method: 'POST', headers: {
                        'Content-Type': 'application/json', 'X-CSRF-Token': motifs['{{$motif->getIdentifier()}}'].csrfToken,
                    },
                });

                // Clear any potential timer to prevent running two at the same time.
                clearInterval(window['statusPolling' + '{{ $motif->getIdentifier() }}']);
                displayState('{{$motif->getIdentifier()}}', 'uploading', null, false);
                startPolling('{{$motif->getIdentifier()}}', this.options.uploadToken);
            });
        },
        accept: function (file, done) {
            // Remove previous elements to maintain a clean overlay.
            this.element.querySelector('.dz-default').style.display = 'none';
            if (errorElement = this.element.querySelector('.dz-error')) {
                errorElement.remove();
            }

            getState('{{ $motif->getIdentifier() }}')
                .then(uploadingStateResponse => {
                    if (uploadingStateResponse.state === 'uploading' || uploadingStateResponse.state === 'processing') {
                        openUploadConfirmModal('{{ $motif->getIdentifier() }}', createCallbackWithArguments(reserveUploadSlot, '{{ $motif->getIdentifier() }}', done));
                    } else {
                        reserveUploadSlot('{{ $motif->getIdentifier() }}', done);
                    }
                })
        },
        success: function (file, response) {
            this.element.querySelector('.dz-default').style.display = 'block';
            this.element.querySelector('.dz-preview')?.remove();

            // Clear the old timer.
            clearInterval(window['statusPolling' + '{{ $motif->getIdentifier() }}']);

            if ('{{ $isImage }}') {
                // Set the newly uploaded image as display image.
                updateImageDisplay('{{$motif->getIdentifier()}}',
                    getImageThumbnailUrl('{{$motif->getIdentifier()}}', response.public_path, '{{ $motif->getTransformations(['height' => 150]) }}', response.version),
                    getFullsizeUrl('{{$motif->getIdentifier()}}', response.public_path, response.version));
            }

            handleUploadResponse(
                file,
                response,
                '{{ $motif->getIdentifier() }}',
                this.options.uploadToken
            );
        },
        error: function (file, response) {
            // Clear the old timer.
            clearInterval(window['statusPolling' + '{{ $motif->getIdentifier() }}']);

            handleUploadResponse(
                file,
                response,
                '{{ $motif->getIdentifier() }}',
                this.options.uploadToken
            );
        },
    });
</script>
