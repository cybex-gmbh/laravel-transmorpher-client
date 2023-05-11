<script src="{{ mix('transmorpher.js', 'vendor/transmorpher') }}"></script>
<link rel="stylesheet" href="{{ mix('transmorpher.css', 'vendor/transmorpher') }}" type="text/css"/>

<div>
    <span id="csrf" class="d-hidden">@csrf</span>
    <div class="card @if(!$isReady) border-processing @endif">
        <div class="card-header">
            {{ $differentiator }}
            <span class="badge @if($isProcessing) badge-processing @else d-hidden @endif">
                Processing
            </span>
            <div class="details">
                @if ($isImage)
                    <a target="_blank" href="{{ $motif->getUrl() }}"><img src="{{ asset('vendor/transmorpher/icons/magnifying-glass.svg') }}" alt="Enlarge image" class="icon"></a>
                @endif
                <img role="button" src="{{ asset('vendor/transmorpher/icons/more-info.svg') }}" alt="More information" class="icon"
                     onclick="openModal('{{ $motif->getIdentifier() }}')">
            </div>
        </div>
        <div class="card-body">
            <form method="POST" class="dropzone" id="dz-{{ $motif->getIdentifier() }}">
                @if ($isImage)
                    <div class="dz-image image-transmorpher">
                        <img data-delivery-url="{{ $motif->getDeliveryUrl() }}"
                             data-placeholder-url="{{ $motif->getPlaceholderUrl() }}"
                             src="{{$motif->getUrl(['height' => 150])}}"
                             alt="{{ $differentiator }}"/>
                    </div>
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
                         class="video-transmorpher @if($isReady) d-none @endif"/>
                @endif
            </form>
        </div>
    </div>

    <div id="modal-{{ $motif->getIdentifier() }}" class="modal d-none">
        <div class="card">
            <div class="card-header">
                {{ $differentiator }}
                <span class="badge @if($isProcessing) badge-processing @else d-hidden @endif">
                    Processing
                </span>
                <button class="btn-close" onclick="closeModal('{{ $motif->getIdentifier() }}')">⨉</button>
            </div>
            <div class="card-body">
                <div class="version-information">
                    <p>Current version: <span class="current-version"></span></p>
                    @if(!$isImage)
                        <p>Currently processed version: <span class="processed-version"></span></p>
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
</div>

<script type="text/javascript">
    Dropzone.autoDiscover = false;

    motifs['{{ $motif->getIdentifier() }}'] = {
        transmorpherMediaKey: {{ $transmorpherMediaKey }},
        csrfToken: document.querySelector('#csrf > input[name="_token"]').value,
        routes: {
            stateUpdate: '{{ $stateUpdateRoute }}',
            handleUploadResponse: '{{ $handleUploadResponseRoute }}',
            getVersions: '{{ $getVersionsRoute }}',
            setVersion: '{{ $setVersionRoute }}',
            delete: '{{ $deleteRoute }}',
            getOriginal: '{{ $getOriginalRoute }}'
        },
        isImage: '{{ $isImage }}'
    }

    // Start polling if the video is still processing.
    // Also set status display for the more information modal.
    if ('{{ !$isImage }}' && '{{ $isProcessing }}') {
        startPolling('{{ $motif->getIdentifier() }}', '{{ $latestUploadToken }}');
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
        init: function () {
            // Remove all other files when a new file is dropped in. Only 1 simultaneous upload is allowed.
            this.on('addedfile', function () {
                if (this.files[1] != null) {
                    this.removeFile(this.files[0]);
                }
            });
        },
        accept: function (file, done) {
            // Remove previous elements to maintain a clean overlay.
            this.element.querySelector('.dz-default').style.display = 'none';
            if (errorElement = this.element.querySelector('.dz-error')) {
                errorElement.remove();
            }

            // Reserve an upload slot at the Transmorpher media server.
            fetch('{{ $uploadTokenRoute }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': motifs['{{ $motif->getIdentifier() }}'].csrfToken,
                },
                body: JSON.stringify({
                    transmorpher_media_key: {{ $transmorpherMediaKey }},
                }),
            }).then(response => {
                return response.json();
            }).then(getUploadTokenResult => {
                if (!getUploadTokenResult.success) {
                    done(getUploadTokenResult);
                }

                this.options.uploadToken = getUploadTokenResult.upload_token
                // Set the dropzone target to the media server upload url, which needs a valid upload token.
                this.options.url = '{{ $motif->getWebUploadUrl() }}' + getUploadTokenResult.upload_token;
                done()
            });
        },
        success: function (file, response) {
            this.element.querySelector('.dz-default').style.display = 'block';
            this.element.querySelector('.dz-preview').remove();

            // Clear the old timer.
            clearInterval(window['statusPolling' + '{{ $motif->getIdentifier() }}']);
            // Set the newly uploaded image as display image.
            updateImageDisplay('{{$motif->getIdentifier()}}', response.public_path, '{{ $motif->getTransformations(['height' => 150]) }}', response.version)

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
