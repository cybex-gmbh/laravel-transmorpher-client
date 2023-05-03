import Dropzone from "dropzone";

if (!window.transmorpherScriptLoaded) {
    window.transmorpherScriptLoaded = true;
    window.Dropzone = Dropzone;

    window.startPolling = function (transmorpherStateUpdateRoute, transmorpherMediaKey, transmorpherIdentifier, uploadToken, csrfToken, card, cardHeader) {
        let statusPollingVariable = `statusPolling${transmorpherIdentifier}`
        let startTime = new Date().getTime();
        window[statusPollingVariable] = setInterval(function () {
            if (new Date().getTime() - startTime > (1 * 60 * 60 * 24 * 1000)) {
                clearInterval(window[statusPollingVariable]);
            }
            fetch(transmorpherStateUpdateRoute, {
                method: "POST", headers: {
                    "Content-Type": "application/json", "X-CSRF-Token": csrfToken,
                }, body: JSON.stringify({
                    upload_token: uploadToken,
                }),
            }).then(response => {
                return response.json();
            }).then(data => {
                if (data.state === 'success') {
                    setStatusDisplay(card, cardHeader, 'success');
                    document.querySelector(`#${transmorpherIdentifier} > .video-transmorpher`).src = data.url;
                    clearInterval(window[statusPollingVariable]);
                } else if (data.state !== 'processing') {
                    setStatusDisplay(card, cardHeader, 'error');
                    clearInterval(window[statusPollingVariable]);
                    displayError(data.response, transmorpherIdentifier);
                }
            })
        }, 5000); // Poll every 5 seconds
    }

    window.handleUploadResponse = function (file, response, transmorpherHandleUploadResponseRoute, idToken, transmorpherMediaKey, transmorpherIdentifier, transmorpherStateUpdateRoute, uploadToken) {
        let csrfToken = document.querySelector("#" + transmorpherIdentifier + " > input[name='_token']").value
        fetch(transmorpherHandleUploadResponseRoute, {
            method: "POST", headers: {
                "Content-Type": "application/json", "X-CSRF-Token": csrfToken,
            }, body: JSON.stringify({
                transmorpher_media_key: transmorpherMediaKey, id_token: idToken, response: response
            })
        }).then(response => {
            return response.json();
        }).then(data => {
            handleDropzoneResult(data, transmorpherIdentifier, transmorpherStateUpdateRoute, transmorpherMediaKey, csrfToken, uploadToken);
        });
    }

    window.handleDropzoneResult = function (data, transmorpherIdentifier, transmorpherStateUpdateRoute, transmorpherMediaKey, csrfToken, uploadToken) {
        let form = document.querySelector("#" + transmorpherIdentifier);
        // form.querySelectorAll('.dz-preview').forEach(element => element.remove())
        let card = form.closest('.card');
        let cardHeader = card.querySelector('.badge');
        if (data.success) {
            form.classList.remove('dz-started');

            if (!form.querySelector('div.dz-image.image-transmorpher > img')) {
                setStatusDisplay(card, cardHeader, 'processing');
                startPolling(transmorpherStateUpdateRoute, transmorpherMediaKey, transmorpherIdentifier, uploadToken, csrfToken, card, cardHeader)
            } else {
                setStatusDisplay(card, cardHeader, 'success');
            }
        } else {
            setStatusDisplay(card, cardHeader, 'error');
            displayError(data.response, transmorpherIdentifier);
        }
    }

    window.setStatusDisplay = function (card, cardHeader, state) {
        card.className = '';
        cardHeader.className = '';
        card.classList.add('card', `border-${state}`);
        cardHeader.classList.add('badge', `badge-${state}`);
        cardHeader.textContent = state;
    }

    window.displayError = function (message, transmorpherIdentifier) {
        let form = document.querySelector("#" + transmorpherIdentifier);
        if (!form.querySelector('.dz-preview')) {
            form.innerHTML = form.innerHTML + form.dropzone.options.previewTemplate;
        }

        let errorMessage = form.querySelector('.dz-error-message')
        errorMessage.replaceChildren();
        errorMessage.append(message);
        errorMessage.style.display = 'block';
        form.querySelector('.dz-preview').classList.add('dz-error');

        form.querySelector('.dz-default').style.display = 'none';
        form.querySelector('.dz-progress').style.display = 'none';
        form.querySelector('.dz-details').style.display = 'none';
    }
}
