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
                } else if (data.state === 'error') {
                    setStatusDisplay(card, cardHeader, 'error');
                    clearInterval(window[statusPollingVariable]);
                } else if (data.state === 'deleted') {
                    setStatusDisplay(card, cardHeader, 'error')
                    clearInterval(window[statusPollingVariable]);
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
        let card = form.closest('.card');
        let cardHeader = card.querySelector('.badge');
        if (data.success) {
            form.classList.remove('dz-started');
            form.querySelector('.dz-preview').remove();

            if (!form.querySelector('div.dz-image.image-transmorpher > img')) {
                setStatusDisplay(card, cardHeader, 'processing');
                startPolling(transmorpherStateUpdateRoute, transmorpherMediaKey, transmorpherIdentifier, uploadToken, csrfToken, card, cardHeader)
            } else {
                setStatusDisplay(card, cardHeader, 'success');
            }
        } else {
            setStatusDisplay(card, cardHeader, 'error');

            let errorMessage = form.querySelector('.dz-error-message')
            errorMessage.replaceChildren();
            errorMessage.append(data.response);

            form.querySelector('.dz-preview').classList.add('dz-error');
        }
    }

    window.setStatusDisplay = function (card, cardHeader, state) {
        card.className = '';
        cardHeader.className = '';
        card.classList.add('card', `border-${state}`);
        cardHeader.classList.add('badge', `badge-${state}`);
        cardHeader.textContent = state;
    }
}
