<script src="https://unpkg.com/dropzone@5/dist/min/dropzone.min.js"></script>
<link rel="stylesheet" href="https://unpkg.com/dropzone@5/dist/min/dropzone.min.css" type="text/css"/>

<style>
    .card {
        display: flex;
        flex-direction: column;
        width: 200px;
        padding: 10px;
        border: black 1px solid;
        border-radius: 15px;
    }

    .card-header {
        display: flex;
        justify-content: center;
    }

    .card-body {

    }
</style>

<div class="card">
    <div class="card-header">
        {{ $videoTransmorpher->getTransmorpherMedia()->differentiator }}
    </div>
    <div class="card-body">
        <form method="POST" class="dropzone" id="{{ $videoTransmorpher->getIdentifier() }}" action="{{ $videoTransmorpher->getApiUrl('video/upload') }}">
            @csrf
        </form>
    </div>
</div>


<script type="text/javascript">
    Dropzone.autoDiscover = false;

    new Dropzone("#{{$videoTransmorpher->getIdentifier()}}", {
        {{--url: '{{ $videoTransmorpher->getApiUrl('video/upload') }}',--}}
        url: 'http://transmorpher.test/api/video/upload',
        chunking: true,
        chunkSize: 1000000, // 1MB
        // retryChunks: true, // default: 3 retries
        parallelChunkUploads: true,
        maxFilesize: 100, // MB
        maxThumbnailFilesize: 100,
        timeout: 60000, // ms
        uploadMultiple: false,
        paramName: 'video',
        idToken: null,
        accept: function (file, done) {
            fetch('{{ route('transmorpherVideoToken') }}', {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-Token": document.querySelector("#{{$videoTransmorpher->getIdentifier()}} > input[name='_token']").value,
                },
                body: JSON.stringify({
                    transmorpher_media_key: {{ $videoTransmorpher->getTransmorpherMedia()->getKey() }},
                }),
            }).then(response => {
                return response.json();
            }).then(data => {
                this.options.params = function (files, xhr, chunk) {
                    if (chunk) {
                        return {
                            dzuuid: chunk.file.upload.uuid,
                            dzchunkindex: chunk.index,
                            dztotalfilesize: chunk.file.size,
                            dzchunksize: this.options.chunkSize,
                            dztotalchunkcount: chunk.file.upload.totalChunkCount,
                            dzchunkbyteoffset: chunk.index * this.options.chunkSize,
                            upload_token: data.upload_token
                        };
                    } else {
                        return {
                            upload_token: data.upload_token
                        }
                    }
                }
                this.options.idToken = data.id_token;
                done()
            });
        },
        chunksUploaded: function (file, done) {
            done();
        },
        success: function (file, response) {
            handleVideoUploadResponse(file, response, this.options.idToken)
        },
        error: function (file, response) {
            handleVideoUploadResponse(file, response, this.options.idToken)
        },
    });

    function handleVideoUploadResponse(file, response, idToken) {
        fetch('{{ route('transmorpherHandleUploadResponse') }}', {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-Token": document.querySelector("#{{$videoTransmorpher->getIdentifier()}} > input[name='_token']").value,
            },
            body: JSON.stringify({
                transmorpher_media_key: {{ $videoTransmorpher->getTransmorpherMedia()->getKey() }},
                id_token: idToken,
                response: response
            })
        }).then(response => {
            return response.json();
        }).then(data => {
            // error or success handling
        });
    }
</script>