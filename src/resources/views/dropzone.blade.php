<script src="{{ mix('transmorpher.js', 'vendor/transmorpher') }}"></script>
<link rel="stylesheet" href="{{ mix('transmorpher.css', 'vendor/transmorpher') }}" type="text/css"/>

<div class="transmorpher" @style(['width: ' . $width => $width])>
    <div id="component-{{ $media->getIdentifier() }}">
        <div @class(['card', 'border-processing' => !$isReady || $isProcessing])>
            <div class="card-header">
                <div>
                    <img src="{{ mix(sprintf('icons/%s.svg', $mediaType->value), 'vendor/transmorpher') }}"
                         alt="@lang('transmorpher::image-alt-tags.icon', ['iconFor' => $mediaType->value])" class="icon">
                    {{ $mediaName }}
                </div>
                <div class="details">
                    <img role="button" src="{{ mix('icons/more-info.svg', 'vendor/transmorpher') }}" alt="@lang('transmorpher::image-alt-tags.open_more_information_modal')"
                         class="icon"
                         onclick="openMoreInformationModal('{{ $media->getIdentifier() }}')">
                </div>
            </div>
            <div class="card-body">
                <div @class(['badge', 'badge-processing' => $isProcessing, 'badge-uploading' => $isUploading, 'd-hidden' => !$isProcessing && !$isUploading])>
                <span>
                    @if($isProcessing)
                        @lang('transmorpher::dropzone.processing')
                    @else
                        @lang('transmorpher::dropzone.uploading')
                    @endif
                </span>
                </div>
                <form method="POST" class="dropzone" id="dz-{{ $media->getIdentifier() }}">
                    <div class="media-preview">
                        <div class="error-display d-none">
                            <span class="error-message"></span>
                            <button type="button" class="btn-close" onclick="closeErrorMessage(this, '{{ $media->getIdentifier() }}')">⨉</button>
                        </div>
                        <x-dynamic-component :component="sprintf('transmorpher::%s-preview', $mediaType->value)" :media="$media"/>
                    </div>
                </form>
            </div>
        </div>

        <div id="modal-mi-{{ $media->getIdentifier() }}" class="modal more-information-modal d-none">
            <div class="card">
                <div class="card-header">
                    <div class="media-name">
                        <img src="{{ mix(sprintf('icons/%s.svg', $mediaType->value), 'vendor/transmorpher') }}"
                             alt="@lang('transmorpher::image-alt-tags.icon', ['iconFor' => $mediaType->value])" class="icon">
                        {{ $mediaName }}
                    </div>
                    <button class="btn-close" onclick="closeMoreInformationModal('{{ $media->getIdentifier() }}')">⨉</button>
                </div>
                <div class="card-body">
                    <div class="card-side">
                        <div @class(['badge', 'badge-processing' => $isProcessing, 'badge-uploading' => $isUploading, 'd-none' => !$isProcessing && !$isUploading])>
                        <span>
                            @if($isProcessing)
                                @lang('transmorpher::dropzone.processing')
                            @else
                                @lang('transmorpher::dropzone.uploading')
                            @endif
                        </span>
                            <div>
                                <p @class(['d-none' => !$isProcessing || !$isUploading])>@lang('transmorpher::dropzone.started') <span class="age"></span></p>
                            </div>
                            <span class="error-message"></span>
                        </div>
                        <span class="current-version-age"></span>
                        <div class="media-preview">
                            <x-dynamic-component :component="sprintf('transmorpher::%s-preview', $mediaType->value)" :media="$media"/>
                        </div>
                        <button type=button @class(['button', 'button-confirm', 'confirm-delete', 'd-hidden' => !$isReady && !$isProcessing])>
                            <span>@lang('transmorpher::dropzone.delete')</span>
                            <img src="{{ mix('icons/delete.svg', 'vendor/transmorpher') }}" alt="@lang('transmorpher::image-alt-tags.icon', ['iconFor' => 'Delete media'])"
                                 class="icon">
                        </button>
                    </div>
                    <div class="card-main">
                        <div class="version-list">
                            <ul>
                                <li class="version-entry d-none">
                                    <x-transmorpher::version-card :media="$media"></x-transmorpher::version-card>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="modal-uc-{{ $media->getIdentifier() }}" class="modal uc-modal d-none">
        <div class="card">
            <div class="card-header">
                @switch($mediaType)
                    @case(\Transmorpher\Enums\MediaType::IMAGE)
                        @lang('transmorpher::dropzone.image_in_process')
                        @break
                    @case(\Transmorpher\Enums\MediaType::VIDEO)
                        @lang('transmorpher::dropzone.video_in_process')
                        @break
                @endswitch
            </div>
            <div class="card-body">
                <button class="button" onclick="closeUploadConfirmModal('{{ $media->getIdentifier() }}')">
                    @lang('transmorpher::dropzone.cancel')
                </button>
                <button class="button badge-error">
                    @lang('transmorpher::dropzone.overwrite')
                </button>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    mediaTypes = @json($mediaTypes);
    transformations = @json($srcSetTransformations);
    translations = @json($translations);
    media['{{ $media->getIdentifier() }}'] = {
        transmorpherMediaKey: {{ $transmorpherMediaKey }},
        routes: {
            state: '{{ $stateRoute }}',
            handleUploadResponse: '{{ $handleUploadResponseRoute }}',
            getVersions: '{{ $getVersionsRoute }}',
            setVersion: '{{ $setVersionRoute }}',
            delete: '{{ $deleteRoute }}',
            getOriginal: '{{ $getOriginalRoute }}',
            getDerivativeForVersion: '{{ $getDerivativeForVersionRoute }}',
            uploadToken: '{{ $uploadTokenRoute }}',
            setUploadingState: '{{ $setUploadingStateRoute }}'
        },
        webUploadUrl: '{{ $media->getWebUploadUrl() }}',
        mediaType: '{{ $mediaType->value }}',
        chunkSize: {{ $media->getChunkSize() }},
        maxFilesize: {{ $media->getMaxFileSize() }},
        maxThumbnailFilesize: {{ $media->getMaxFileSize() }},
        isProcessing: @json($isProcessing),
        isUploading: @json($isUploading),
        lastUpdated: '{{ $lastUpdated }}',
        latestUploadToken: '{{ $latestUploadToken }}',
        acceptedFileTypes: '{{ $media->getAcceptedFileTypes() }}',
        minWidth: '{{ $acceptedMinWidth }}',
        maxWidth: '{{ $acceptedMaxWidth }}',
        minHeight: '{{ $acceptedMinHeight }}',
        maxHeight: '{{ $acceptedMaxHeight }}',
        ratio: '{{ $acceptedRatio }}'
    }

    setupComponent('{{ $media->getIdentifier() }}');
</script>
