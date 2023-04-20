import Dropzone from "dropzone"

window.Dropzone = Dropzone;

window.handleUploadResponse = function(file, response, transmorpherHandleUploadResponseRoute, idToken, transmorpherMediaKey, transmorpherIdentifier) {
    fetch(transmorpherHandleUploadResponseRoute, {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-Token": document.querySelector("#" + transmorpherIdentifier + " > input[name='_token']").value,
        },
        body: JSON.stringify({
            transmorpher_media_key: transmorpherMediaKey,
            id_token: idToken,
            response: response
        })
    }).then(response => {
        return response.json();
    }).then(data => {
        handleDropzoneResult(data, transmorpherIdentifier);
    });
}

window.handleDropzoneResult = function(data, transmorpherIdentifier) {
    let form = document.querySelector("#" + transmorpherIdentifier);
    let card = form.closest('.card');
    if (!data.success) {
        let errorMessage = form.querySelector('.dz-error-message')
        form.querySelector('.dz-preview').classList.add('dz-error');
        errorMessage.append(data.response);
        card.setAttribute('class', 'card border-error');
    } else {
        form.querySelector('.dz-preview').remove();
        card.setAttribute('class', 'card border-success');
    }
}