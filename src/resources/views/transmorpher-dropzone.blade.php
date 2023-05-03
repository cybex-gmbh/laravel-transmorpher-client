<script src="{{ mix('transmorpher.js', 'vendor/transmorpher') }}"></script>
<link rel="stylesheet" href="{{ mix('transmorpher.css', 'vendor/transmorpher') }}" type="text/css"/>

<div class="card @if(!$motif->getTransmorpherMedia()->is_ready) border-processing @endif">
    <div class="card-header">
        {{$motif->getTransmorpherMedia()->differentiator}}
        <span class="badge @if($motif->getTransmorpherMedia()->last_response === \Transmorpher\Enums\State::PROCESSING) badge-processing @else d-none @endif">
            @if($motif->getTransmorpherMedia()->last_response === \Transmorpher\Enums\State::PROCESSING)
                Processing
            @endif
        </span>
    </div>
    <div class="card-body">
        <form method="POST" class="dropzone" id="{{ $motif->getIdentifier() }}">
            @csrf
            @if ($motif->getTransmorpherMedia()->type === \Transmorpher\Enums\MediaType::IMAGE)
                <div class="dz-image image-transmorpher">
                    <img data-delivery-url="{{ $motif->getDeliveryUrl() }}"
                         src="{{$motif->getUrl(['height' => 150])}}"
                         alt="{{$motif->getTransmorpherMedia()->differentiator}}"/>
                </div>
            @else
                @if ($motif->getTransmorpherMedia()->is_ready)
                    <video preload="metadata" controls style="height:150px" class="video-transmorpher">
                        <source src="{{ $motif->getMp4Url() }}" type="video/mp4">
                        <p style="padding: 5px;">
                            Your browser doesn't support HTML video. Here is a
                            <a href="{{ $motif->getMp4Url() }}">link to the video</a> instead.
                        </p>
                    </video>
                @else
                    <img src="{{$motif->getUrl()}}"
                         alt="{{$motif->getTransmorpherMedia()->differentiator}}"
                         class="video-transmorpher"/>
                @endif
            @endif
        </form>
    </div>
</div>

<script type="text/javascript">
    Dropzone.autoDiscover = false;

    form = document.querySelector('#{{$motif->getIdentifier()}}');
    csrfToken = document.querySelector('#{{$motif->getIdentifier()}} > input[name="_token"]').value
    card = form.closest('.card');
    cardHeader = card.querySelector('.badge');

    if (form.querySelector('.video-transmorpher') && cardHeader.classList.contains('badge-processing')) {
        startPolling('{{ route('transmorpherStateUpdate', $motif->getTransmorpherMedia()->getKey()) }}', {{ $motif->getTransmorpherMedia()->getKey() }}, '{{$motif->getIdentifier()}}', '{{$motif->getTransmorpherMedia()->last_upload_token}}', csrfToken, card, cardHeader)
    }

    new Dropzone("#{{$motif->getIdentifier()}}", {
        url: '{{ $motif->getWebUploadUrl() }}',
        chunking: true,
        chunkSize: {{ $motif->getChunkSize() }},
        maxFilesize: {{ $motif->getMaxFileSize() }},
        maxThumbnailFilesize: {{ $motif->getMaxFileSize() }},
        timeout: 60000,
        uploadMultiple: false,
        paramName: 'file',
        idToken: null,
        uploadToken: null,
        init: function () {
            this.on("addedfile", function () {
                if (this.files[1] != null) {
                    this.removeFile(this.files[0]);
                }
            });
        },
        accept: function (file, done) {
            if (errorElement = this.element.querySelector('.dz-error')) {
                errorElement.remove();
            }
            fetch('{{ route('transmorpherUploadToken', $motif->getTransmorpherMedia()->getKey()) }}', {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-Token": csrfToken,
                },
                body: JSON.stringify({
                    transmorpher_media_key: {{ $motif->getTransmorpherMedia()->getKey() }},
                }),
            }).then(response => {
                return response.json();
            }).then(data => {
                if (!data.success) {
                    this.options.idToken = data.id_token;
                    done(data);
                }
                this.options.uploadToken = data.upload_token
                this.options.url = '{{ $motif->getWebUploadUrl() }}' + data.upload_token;
                this.options.params = function (files, xhr, chunk) {
                    if (chunk) {
                        return {
                            dzuuid: chunk.file.upload.uuid,
                            dzchunkindex: chunk.index,
                            dztotalfilesize: chunk.file.size,
                            dzchunksize: this.options.chunkSize,
                            dztotalchunkcount: chunk.file.upload.totalChunkCount,
                            dzchunkbyteoffset: chunk.index * this.options.chunkSize,
                        };
                    }
                }
                this.options.idToken = data.id_token;
                done()
            });
        },
        success: function (file, response) {
            this.element.querySelector('.dz-default').style.display = 'block';
            this.element.querySelector('.dz-preview').remove();
            clearInterval(window['statusPolling' + '{{$motif->getIdentifier()}}']);

            if (imgElement = this.element.querySelector('div.dz-image.image-transmorpher > img')) {
                imgElement.src = imgElement.dataset.deliveryUrl + '/' + response.public_path + '/' + '{{ $motif->getTransformations(['height' => 150]) }}' + '?v=' + response.version;
            }

            handleUploadResponse(
                file,
                response,
                '{{ route('transmorpherHandleUploadResponse', $motif->getTransmorpherMedia()->getKey()) }}',
                this.options.idToken,
                    {{ $motif->getTransmorpherMedia()->getKey() }},
                '{{$motif->getIdentifier()}}',
                '{{ route('transmorpherStateUpdate', $motif->getTransmorpherMedia()->getKey()) }}',
                this.options.uploadToken
            );
        },
        error: function (file, response) {
            clearInterval(window['statusPolling' + '{{$motif->getIdentifier()}}']);

            handleUploadResponse(
                file,
                response,
                '{{ route('transmorpherHandleUploadResponse', $motif->getTransmorpherMedia()->getKey()) }}',
                this.options.idToken,
                    {{ $motif->getTransmorpherMedia()->getKey() }},
                '{{$motif->getIdentifier()}}',
                '{{ route('transmorpherStateUpdate', $motif->getTransmorpherMedia()->getKey()) }}',
                this.options.uploadToken
            );
        },
    });
</script>
