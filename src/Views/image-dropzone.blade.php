<script src="https://unpkg.com/dropzone@5/dist/min/dropzone.min.js"></script>
<link rel="stylesheet" href="https://unpkg.com/dropzone@5/dist/min/dropzone.min.css" type="text/css"/>

<form method="POST" id="test" class="dropzone" action="http://transmorpher.test/api/image/upload">
    @csrf
</form>

<script type="text/javascript">
    const csrfToken = document.querySelector("input[name='_token']").value;
    let transmorpherMediaKey = {{ App\Models\User::first()->image()->getTransmorpherMedia()->getKey() }};
    let idToken;
    let uploadToken;

    Dropzone.options.test = {
        url: 'http://transmorpher.test/api/image/upload',
        maxFilesize: 80, // MB
        maxThumbnailFilesize: 80,
        timeout: 60000, // ms
        uploadMultiple: false,
        paramName: 'image',
        headers: {
            'Authorization': 'Bearer 2|ruWCzoepgETQOQNWwbHIKcQbODtjtoA6NuISQzy4'
        },
        params: {
            upload_token: null
        },
        accept: function (file, done) {
            fetch('/transmorpher/image/token', {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-Token": csrfToken,
                },
                body: JSON.stringify({
                    transmorpher_media_key: transmorpherMediaKey
                })
            }).then((response) => {
                return response.json();
            }).then((data) => {
                console.log(data);
                idToken = data.id_token;
                Dropzone.options.test.params.upload_token = data.upload_token;
                console.log(Dropzone.options.test);
                done()
            });
        },
        success: function () {
            alert('worked');
        },
        error: function () {
            alert('failed');
        }
    }
</script>