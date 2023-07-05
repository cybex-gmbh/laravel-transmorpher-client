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
            <div @class(['badge', 'badge-processing' => $isProcessing, 'badge-uploading' => $isUploading, 'd-hidden' => !$isProcessing && !$isUploading])>
                <span>
                    @if($isProcessing)
                        Processing
                    @else
                        Uploading
                    @endif
                </span>
            </div>
            <form method="POST" class="dropzone" id="dz-{{ $motif->getIdentifier() }}">
                <div class="media-preview">
                    <div class="error-display d-none">
                        <span class="error-message"></span>
                        <button type="button" class="btn-close" onclick="closeErrorMessage(this, '{{ $motif->getIdentifier() }}')">⨉</button>
                    </div>
                    <x-dynamic-component :component="sprintf('transmorpher::%s-preview', $mediaType->value)" :motif="$motif"/>
                </div>
            </form>
        </div>
    </div>

    <div id="modal-mi-{{ $motif->getIdentifier() }}" class="modal more-information-modal d-none">
        <div class="card">
            <div class="card-header">
                <div class="motif-name">
                    <img src="{{ mix(sprintf('icons/%s.svg', $mediaType->value), 'vendor/transmorpher') }}"
                         alt="{{ $mediaType->value }}" class="icon">
                    {{ $differentiator }}
                </div>
                <button class="btn-close" onclick="closeMoreInformationModal('{{ $motif->getIdentifier() }}')">⨉</button>
            </div>
            <div class="card-body">
                <div class="card-side">
                    <div @class(['badge', 'badge-processing' => $isProcessing, 'badge-uploading' => $isUploading, 'd-none' => !$isProcessing && !$isUploading])>
                        <span>
                            @if($isProcessing)
                                Processing
                            @else
                                Uploading
                            @endif
                        </span>
                        <div class="motif-info">
                            <p @class(['d-none' => !$isProcessing || !$isUploading])>started <span class="age"></span></p>
                        </div>
                        <span class="error-message"></span>
                    </div>
                    <span class="current-version-age"></span>
                    <div class="media-preview">
                        <x-dynamic-component :component="sprintf('transmorpher::%s-preview', $mediaType->value)" :motif="$motif"/>
                    </div>
                    <button type=button @class(['button', 'button-confirm', 'confirm-delete', 'd-hidden' => !$isReady && !$isProcessing])>
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
    mediaTypes = {!! json_encode($mediaTypes) !!};
    transformations = {!! json_encode($srcSetTransformations) !!};
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
        mediaType: '{{ $mediaType->value }}',
        chunkSize: {{ $motif->getChunkSize() }},
        maxFilesize: {{ $motif->getMaxFileSize() }},
        maxThumbnailFilesize: {{ $motif->getMaxFileSize() }},
        isProcessing: {{ json_encode($isProcessing) }},
        isUploading: {{ json_encode($isUploading) }},
        lastUpdated: '{{ $lastUpdated }}',
        latestUploadToken: '{{ $latestUploadToken }}',
        acceptedFileTypes: '{{ $motif->getAcceptedFileTypes() }}'
    }

    setupComponent('{{ $motif->getIdentifier() }}');
</script>
