import Dropzone from 'dropzone';

if (!window.transmorpherScriptLoaded) {
    window.transmorpherScriptLoaded = true;
    window.Dropzone = Dropzone;
    window.motifs = [];

    window.startPolling = function (transmorpherIdentifier, uploadToken) {
        let statusPollingVariable = `statusPolling${transmorpherIdentifier}`
        let startTime = new Date().getTime();

        // Set a timer to start polling for new information on the status of the processing video.
        // Has to be stored in a global variable, to be able to clear the timer when a new video is dropped in the dropzone.
        window[statusPollingVariable] = setInterval(function () {
            // Clear timer after 24 hours.
            if (new Date().getTime() - startTime > (1 * 60 * 60 * 24 * 1000)) {
                clearInterval(window[statusPollingVariable]);
            }

            // Poll for status updates.
            fetch(motifs[transmorpherIdentifier].routes.stateUpdate, {
                method: 'POST', headers: {
                    'Content-Type': 'application/json', 'X-CSRF-Token': motifs[transmorpherIdentifier].csrfToken,
                }, body: JSON.stringify({
                    upload_token: uploadToken,
                }),
            }).then(response => {
                return response.json();
            }).then(data => {
                if (data.state === 'success') {
                    setStatusDisplay(transmorpherIdentifier, 'success');
                    document.querySelector(`#${transmorpherIdentifier} > .video-transmorpher`).src = data.url;
                    clearInterval(window[statusPollingVariable]);
                } else if (data.state !== 'processing') {
                    setStatusDisplay(transmorpherIdentifier, 'error');
                    clearInterval(window[statusPollingVariable]);
                    displayError(data.response, transmorpherIdentifier);
                }
            })
        }, 5000); // Poll every 5 seconds
    }

    window.handleUploadResponse = function (file, response, transmorpherIdentifier, uploadToken) {
        fetch(motifs[transmorpherIdentifier].routes.handleUploadResponse, {
            method: 'POST', headers: {
                'Content-Type': 'application/json', 'X-CSRF-Token': motifs[transmorpherIdentifier].csrfToken,
            }, body: JSON.stringify({
                transmorpher_media_key: motifs[transmorpherIdentifier].transmorpherMediaKey, upload_token: uploadToken, response: response
            })
        }).then(response => {
            return response.json();
        }).then(data => {
            handleDropzoneResult(data, transmorpherIdentifier, uploadToken);
        });
    }

    window.handleDropzoneResult = function (data, transmorpherIdentifier, uploadToken) {
        let form = document.querySelector('#' + transmorpherIdentifier);

        if (data.success) {
            form.classList.remove('dz-started');

            if (!form.querySelector('div.dz-image.image-transmorpher > img')) {
                // It's a video dropzone, indicate that it is now processing and start polling for updates.
                setStatusDisplay(transmorpherIdentifier, 'processing');
                startPolling(transmorpherIdentifier, uploadToken)
            } else {
                setStatusDisplay(transmorpherIdentifier, 'success');
            }
        } else {
            setStatusDisplay(transmorpherIdentifier, 'error');
            displayError(data.response, transmorpherIdentifier);
        }
    }

    window.setStatusDisplay = function (transmorpherIdentifier, state) {
        let form = document.querySelector('#' + transmorpherIdentifier);
        let card = form.closest('.card');
        let cardHeader = card.querySelector('.badge');

        card.className = '';
        cardHeader.className = '';
        card.classList.add('card', `border-${state}`);
        cardHeader.classList.add('badge', `badge-${state}`);
        cardHeader.textContent = state;
    }

    window.displayError = function (message, transmorpherIdentifier) {
        let form = document.querySelector('#' + transmorpherIdentifier);
        // Add preview element, which also displays errors, when it is not present yet.
        if (!form.querySelector('.dz-preview')) {
            form.innerHTML = form.innerHTML + form.dropzone.options.previewTemplate;
        }

        let errorMessage = form.querySelector('.dz-error-message')
        errorMessage.replaceChildren();
        errorMessage.append(message);
        errorMessage.style.display = 'block';
        form.querySelector('.dz-preview').classList.add('dz-error');

        // Remove visual clutter.
        form.querySelector('.dz-default').style.display = 'none';
        form.querySelector('.dz-progress').style.display = 'none';
        form.querySelector('.dz-details').style.display = 'none';
    }
}
