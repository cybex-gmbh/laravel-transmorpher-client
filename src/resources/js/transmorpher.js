import Dropzone from "dropzone";

if (!window.transmorpherScriptLoaded) {
    window.transmorpherScriptLoaded = true;
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
        let cardHeader = card.querySelector('.badge');
        if (data.success) {
            form.classList.remove('dz-started');
            form.querySelector('.dz-preview').remove();

            card.className = '';
            card.classList.add('card', 'border', 'border-success');
            cardHeader.className = '';
            cardHeader.classList.add('badge', 'badge-success');
            cardHeader.textContent = "Success";
        } else {
            card.className = '';
            card.classList.add('card', 'border', 'border-error');
            cardHeader.className = '';
            cardHeader.classList.add('badge', 'badge-error');
            cardHeader.textContent = "Error";

            let errorMessage = form.querySelector('.dz-error-message')
            errorMessage.replaceChildren();
            errorMessage.append(data.response);

            form.querySelector('.dz-preview').classList.add('dz-error');
        }
    }
}
