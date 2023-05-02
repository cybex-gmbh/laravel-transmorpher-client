<script src="{{ mix('transmorpher.js', 'vendor/transmorpher') }}"></script>
<link rel="stylesheet" href="{{ mix('transmorpher.css', 'vendor/transmorpher') }}" type="text/css"/>

<div class="card @if(!$transmorpher->getTransmorpherMedia()->is_ready) border-processing @endif">
    <div class="card-header">
        {{$transmorpher->getTransmorpherMedia()->differentiator}}
        <span class="badge @if($transmorpher->getTransmorpherMedia()->last_response === \Transmorpher\Enums\State::PROCESSING) badge-processing @else d-none @endif">
            @if($transmorpher->getTransmorpherMedia()->last_response === \Transmorpher\Enums\State::PROCESSING)
                Processing
            @endif
        </span>
    </div>
    <div class="card-body">
        <form method="POST" class="dropzone" id="{{ $transmorpher->getIdentifier() }}">
            @csrf
            @if ($transmorpher->getTransmorpherMedia()->type === \Transmorpher\Enums\MediaType::IMAGE)
                <div class="dz-image image-transmorpher">
                    <img data-delivery-url="{{ $transmorpher->getDeliveryUrl() }}"
                         src="{{$transmorpher->getUrl(['height' => 150])}}"
                         alt="{{$transmorpher->getTransmorpherMedia()->differentiator}}"/>
                </div>
            @else
                @if ($transmorpher->getTransmorpherMedia()->is_ready)
                    <video preload="metadata" controls style="height:150px" class="video-transmorpher">
                        <source src="{{ $transmorpher->getMp4Url() }}" type="video/mp4">
                        <p style="padding: 5px;">
                            Your browser doesn't support HTML video. Here is a
                            <a href="{{ $transmorpher->getMp4Url() }}">link to the video</a> instead.
                        </p>
                    </video>
                @else
                    <img src="{{$transmorpher->getUrl()}}"
                         alt="{{$transmorpher->getTransmorpherMedia()->differentiator}}"
                         class="video-transmorpher" />
                @endif
            @endif
        </form>
    </div>
</div>

<script type="text/javascript">
    Dropzone.autoDiscover = false;

    form = document.querySelector('#{{$transmorpher->getIdentifier()}}');
    csrfToken = document.querySelector('#{{$transmorpher->getIdentifier()}} > input[name="_token"]').value
    card = form.closest('.card');
    cardHeader = card.querySelector('.badge');

    if (form.querySelector('.video-transmorpher') && cardHeader.classList.contains('badge-processing')) {
        startPolling('{{ route('transmorpherStateUpdate', $transmorpher->getTransmorpherMedia()->getKey()) }}', {{ $transmorpher->getTransmorpherMedia()->getKey() }}, '{{$transmorpher->getIdentifier()}}', '{{$transmorpher->getTransmorpherMedia()->last_upload_token}}', csrfToken, card, cardHeader)
    }

    new Dropzone("#{{$transmorpher->getIdentifier()}}", {
        url: '{{ $transmorpher->getWebUploadUrl() }}',
        chunking: true,
        chunkSize: {{ $transmorpher->getChunkSize() }},
        maxFilesize: {{ $transmorpher->getMaxFileSize() }},
        maxThumbnailFilesize: {{ $transmorpher->getMaxFileSize() }},
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
            fetch('{{ route('transmorpherUploadToken', $transmorpher->getTransmorpherMedia()->getKey()) }}', {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-Token": csrfToken,
                },
                body: JSON.stringify({
                    transmorpher_media_key: {{ $transmorpher->getTransmorpherMedia()->getKey() }},
                }),
            }).then(response => {
                return response.json();
            }).then(data => {
                if (!data.success) {
                    this.options.idToken = data.id_token;
                    done(data);
                }
                this.options.uploadToken = data.upload_token
                this.options.url = '{{ $transmorpher->getWebUploadUrl() }}' + data.upload_token;
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
            clearInterval(window['statusPolling' + '{{$transmorpher->getIdentifier()}}']);

            if (imgElement = this.element.querySelector('div.dz-image.image-transmorpher > img')) {
                imgElement.src = imgElement.dataset.deliveryUrl + '/' + response.public_path + '/' + '{{ $transmorpher->getTransformations(['height' => 150]) }}' + '?v=' + response.version;
            }

            handleUploadResponse(
                file,
                response,
                '{{ route('transmorpherHandleUploadResponse', $transmorpher->getTransmorpherMedia()->getKey()) }}',
                this.options.idToken,
                    {{ $transmorpher->getTransmorpherMedia()->getKey() }},
                '{{$transmorpher->getIdentifier()}}',
                '{{ route('transmorpherStateUpdate', $transmorpher->getTransmorpherMedia()->getKey()) }}',
                this.options.uploadToken
            );
        },
        error: function (file, response) {
            clearInterval(window['statusPolling' + '{{$transmorpher->getIdentifier()}}']);

            handleUploadResponse(
                file,
                response,
                '{{ route('transmorpherHandleUploadResponse', $transmorpher->getTransmorpherMedia()->getKey()) }}',
                this.options.idToken,
                    {{ $transmorpher->getTransmorpherMedia()->getKey() }},
                '{{$transmorpher->getIdentifier()}}',
                '{{ route('transmorpherStateUpdate', $transmorpher->getTransmorpherMedia()->getKey()) }}',
                this.options.uploadToken
            );
        },
    });
</script>
