<script src="{{ mix('transmorpher.js', 'vendor/transmorpher') }}"></script>
<link rel="stylesheet" href="{{ mix('transmorpher.css', 'vendor/transmorpher') }}" type="text/css"/>

<div id="component-{{ $topic->getIdentifier() }}">
    <div @class(['card', 'border-processing' => !$isReady || $isProcessing])>
        <div class="card-header">
            <div>
                <img src="{{ mix(sprintf('icons/%s.svg', $mediaType->value), 'vendor/transmorpher') }}"
                     alt="@lang('transmorpher::image-alt-tags.icon', ['iconFor' => $mediaType->value])" class="icon">
                {{ $topicName }}
            </div>
            <div class="details">
                <img role="button" src="{{ mix('icons/more-info.svg', 'vendor/transmorpher') }}" alt="@lang('transmorpher::image-alt-tags.open_more_information_modal')"
                     class="icon"
                     onclick="openMoreInformationModal('{{ $topic->getIdentifier() }}')">
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
            <form method="POST" class="dropzone" id="dz-{{ $topic->getIdentifier() }}">
                <div class="media-preview">
                    <div class="error-display d-none">
                        <span class="error-message"></span>
                        <button type="button" class="btn-close" onclick="closeErrorMessage(this, '{{ $topic->getIdentifier() }}')">⨉</button>
                    </div>
                    <x-dynamic-component :component="sprintf('transmorpher::%s-preview', $mediaType->value)" :topic="$topic"/>
                </div>
            </form>
        </div>
    </div>

    <div id="modal-mi-{{ $topic->getIdentifier() }}" class="modal more-information-modal d-none">
        <div class="card">
            <div class="card-header">
                <div class="topic">
                    <img src="{{ mix(sprintf('icons/%s.svg', $mediaType->value), 'vendor/transmorpher') }}"
                         alt="@lang('transmorpher::image-alt-tags.icon', ['iconFor' => $mediaType->value])" class="icon">
                    {{ $topicName }}
                </div>
                <button class="btn-close" onclick="closeMoreInformationModal('{{ $topic->getIdentifier() }}')">⨉</button>
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
                        <x-dynamic-component :component="sprintf('transmorpher::%s-preview', $mediaType->value)" :topic="$topic"/>
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
                                <x-transmorpher::version-card :topic="$topic"></x-transmorpher::version-card>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="modal-uc-{{ $topic->getIdentifier() }}" class="modal uc-modal d-none">
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
            <button class="button" onclick="closeUploadConfirmModal('{{ $topic->getIdentifier() }}')">
                @lang('transmorpher::dropzone.cancel')
            </button>
            <button class="button badge-error">
                @lang('transmorpher::dropzone.overwrite')
            </button>
        </div>
    </div>
</div>

<script type="text/javascript">
    mediaTypes = @json($mediaTypes);
    transformations = @json($srcSetTransformations);
    translations = @json($translations);
    topics['{{ $topic->getIdentifier() }}'] = {
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
        webUploadUrl: '{{ $topic->getWebUploadUrl() }}',
        mediaType: '{{ $mediaType->value }}',
        chunkSize: {{ $topic->getChunkSize() }},
        maxFilesize: {{ $topic->getMaxFileSize() }},
        maxThumbnailFilesize: {{ $topic->getMaxFileSize() }},
        isProcessing: @json($isProcessing),
        isUploading: @json($isUploading),
        lastUpdated: '{{ $lastUpdated }}',
        latestUploadToken: '{{ $latestUploadToken }}',
        acceptedFileTypes: '{{ $topic->getAcceptedFileTypes() }}'
    }

    setupComponent('{{ $topic->getIdentifier() }}');
</script>
