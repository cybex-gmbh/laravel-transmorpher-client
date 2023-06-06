<script src="{{ mix('transmorpher.js', 'vendor/transmorpher') }}"></script>
<link rel="stylesheet" href="{{ mix('transmorpher.css', 'vendor/transmorpher') }}" type="text/css"/>

<div id="component-{{ $motif->getIdentifier() }}">
    <div @class(['card', 'border-processing' => !$isReady || $isProcessing])>
        <div class="card-header">
            <div>
                <img src="{{ mix(sprintf('icons/%s.svg', $mediaType->value), 'vendor/transmorpher') }}"
                     alt="{{ $mediaType->value }}" class="icon">
                {{ $differentiator }}
            </div>
            <div class="details">
                <img role="button" src="{{ mix('icons/more-info.svg', 'vendor/transmorpher') }}" alt="More information" class="icon"
                     onclick="openMoreInformationModal('{{ $motif->getIdentifier() }}')">
            </div>
        </div>
        <div class="card-body">
             <span @class(['badge', 'badge-processing' => $isProcessing, 'badge-uploading' => $isUploading, 'd-hidden' => !$isProcessing && !$isUploading])>
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
                    <x-dynamic-component :component="sprintf('transmorpher::%s-preview', $mediaType->value)" :motif="$motif"/>
                </div>
            </form>
        </div>
    </div>

    <div id="modal-mi-{{ $motif->getIdentifier() }}" class="modal more-information-modal d-none">
        <div class="card">
            <div class="card-header">
                <span @class(['badge', 'badge-processing' => $isProcessing, 'badge-uploading' => $isUploading, 'd-hidden' => !$isProcessing && !$isUploading])>
                    @if($isProcessing)
                        Processing
                    @else
                        Uploading
                    @endif
                </span>
                <span class="error-message"></span>
                <button class="btn-close" onclick="closeMoreInformationModal('{{ $motif->getIdentifier() }}')">⨉</button>
            </div>
            <div class="card-body">
                <div class="card-side">
                    <div class="motif-info">
                        <div class="motif-name">
                            <img src="{{ mix(sprintf('icons/%s.svg', $mediaType->value), 'vendor/transmorpher') }}"
                                 alt="{{ $mediaType->value }}" class="icon">
                            {{ $differentiator }}
                        </div>
                        <p @class(['d-none' => !$isProcessing])>Processing started <span class="processing-age"></span></p>
                        <p @class(['d-none' => !$isUploading])">Upload started <span class="upload-age"></span></p>
                    </div>
                    <div class="version-information">
                        <div>
                            <p>Current version: <span class="current-version"></span></p>
                            <p class="age">uploaded <span class="current-version-age"></span></p>
                        </div>
                        @switch($mediaType)
                            @case(\Transmorpher\Enums\MediaType::VIDEO)
                                <div>
                                    <p>Currently processed version: <span class="processed-version"></span></p>
                                    <p class="age">uploaded <span class="processed-version-age"></span></p>
                                </div>
                                @break
                        @endswitch
                    </div>
                    <x-dynamic-component :component="sprintf('transmorpher::%s-preview', $mediaType->value)" :motif="$motif"/>
                    <button type=button class="button button-hold hold-delete">
                        <span>Delete</span>
                        <img src="{{ mix('icons/delete.svg', 'vendor/transmorpher') }}" alt="Delete" class="icon">
                    </button>
                </div>
                <div class="card-main">
                    <div class="version-list">
                        <ul>
                            <li class="version-entry d-none">
                                <x-transmorpher::version-card :motif="$motif"></x-transmorpher::version-card>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="modal-uc-{{ $motif->getIdentifier() }}" class="modal uc-modal d-none">
    <div class="card">
        <div class="card-header">
            @switch($mediaType)
                @case(\Transmorpher\Enums\MediaType::IMAGE)
                    There is currently an upload in process, do you want to overwrite it?
                    @break
                @case(\Transmorpher\Enums\MediaType::VIDEO)
                    A video is currently uploading or processing, do you want to overwrite it?
                    @break
            @endswitch
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

<script type="text/javascript">
    Dropzone.autoDiscover = false;

    mediaTypes = {!! json_encode($mediaTypes) !!};
    motifs['{{ $motif->getIdentifier() }}'] = {
        transmorpherMediaKey: {{ $transmorpherMediaKey }},
        routes: {
            state: '{{ $stateRoute }}',
            handleUploadResponse: '{{ $handleUploadResponseRoute }}',
            getVersions: '{{ $getVersionsRoute }}',
            setVersion: '{{ $setVersionRoute }}',
            delete: '{{ $deleteRoute }}',
            getOriginal: '{{ $getOriginalRoute }}',
            getOriginalDerivative: '{{ $getOriginalDerivativeRoute }}',
            uploadToken: '{{ $uploadTokenRoute }}',
            setUploadingState: '{{ $setUploadingStateRoute }}'
        },
        webUploadUrl: '{{ $motif->getWebUploadUrl() }}',
        mediaType: '{{ $mediaType->value }}'
    }

    addConfirmEventListeners(
        document.querySelector('#modal-mi-{{ $motif->getIdentifier() }} .hold-delete'),
        createCallbackWithArguments(deleteTransmorpherMedia, '{{ $motif->getIdentifier() }}'),
        1500
    );


    // Start polling if the video is still processing or an upload is in process.
    if (('{{ $isProcessing }}') || '{{ $isUploading }}') {
        startPolling('{{ $motif->getIdentifier() }}', '{{ $latestUploadToken }}');
        setAgeElement(document.querySelector('#modal-mi-{{ $motif->getIdentifier() }} .{{ $isProcessing ? 'processing' : 'upload' }}-age'), timeAgo(new Date('{{ $lastUpdated }}' * 1000)));
    }

    new Dropzone('#dz-{{ $motif->getIdentifier() }}', {
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
                fetch(`${motifs['{{$motif->getIdentifier()}}'].routes.setUploadingState}/${this.options.uploadToken}`, {
                    method: 'POST', headers: {
                        'Content-Type': 'application/json', 'X-XSRF-TOKEN': getCsrfToken()
                    },
                });

                // Clear any potential timer to prevent running two at the same time.
                clearInterval(window['statusPolling{{ $motif->getIdentifier() }}']);
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
            clearInterval(window['statusPolling{{ $motif->getIdentifier() }}']);

            handleUploadResponse(
                file,
                response,
                '{{ $motif->getIdentifier() }}',
                this.options.uploadToken
            );
        },
        error: function (file, response) {
            // Clear the old timer.
            clearInterval(window['statusPolling{{ $motif->getIdentifier() }}']);

            handleUploadResponse(
                file,
                response,
                '{{ $motif->getIdentifier() }}',
                this.options.uploadToken
            );
        },
    });
</script>
