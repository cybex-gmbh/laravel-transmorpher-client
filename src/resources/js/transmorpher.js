import Dropzone from 'dropzone';

if (!window.transmorpherScriptLoaded) {
    window.transmorpherScriptLoaded = true;
    window.Dropzone = Dropzone;
    window.motifs = [];

    window.startPolling = function (transmorpherIdentifier, uploadToken) {
        let statusPollingVariable = `statusPolling${transmorpherIdentifier}`
        let startTime = new Date().getTime();
        window[statusPollingVariable] = setInterval(function () {
            if (new Date().getTime() - startTime > (1 * 60 * 60 * 24 * 1000)) {
                clearInterval(window[statusPollingVariable]);
            }
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
                    document.querySelector(`#dz-${transmorpherIdentifier} > .video-transmorpher`).src = data.url;
                    clearInterval(window[statusPollingVariable]);
                } else if (data.state !== 'processing') {
                    setStatusDisplay(transmorpherIdentifier, 'error');
                    clearInterval(window[statusPollingVariable]);
                    displayError(data.response, transmorpherIdentifier);
                }
            })
        }, 5000); // Poll every 5 seconds
    }

    window.handleUploadResponse = function (file, response, transmorpherIdentifier, idToken, uploadToken) {
        fetch(motifs[transmorpherIdentifier].routes.handleUploadResponse, {
            method: 'POST', headers: {
                'Content-Type': 'application/json', 'X-CSRF-Token': motifs[transmorpherIdentifier].csrfToken,
            }, body: JSON.stringify({
                transmorpher_media_key: motifs[transmorpherIdentifier].transmorpherMediaKey, id_token: idToken, response: response
            })
        }).then(response => {
            return response.json();
        }).then(data => {
            handleDropzoneResult(data, transmorpherIdentifier, uploadToken);
        });
    }

    window.handleDropzoneResult = function (data, transmorpherIdentifier, uploadToken) {
        let form = document.querySelector('#dz-' + transmorpherIdentifier);
        let card = form.closest('.card');
        let cardHeader = card.querySelector('.badge');

        if (data.success) {
            form.classList.remove('dz-started');

            if (!form.querySelector('div.dz-image.image-transmorpher > img')) {
                setStatusDisplay(transmorpherIdentifier, 'processing');
                startPolling(transmorpherIdentifier, uploadToken, card, cardHeader)
            } else {
                setStatusDisplay(transmorpherIdentifier, 'success');
            }
        } else {
            setStatusDisplay(transmorpherIdentifier, 'error');
            displayError(data.response, transmorpherIdentifier);
        }
    }

    window.setStatusDisplay = function (transmorpherIdentifier, state) {
        let form = document.querySelector(`#dz-${transmorpherIdentifier}`);
        let card = form.closest('.card');
        let cardHeader = card.querySelector('.badge');

        card.className = '';
        cardHeader.className = '';
        card.classList.add('card', `border-${state}`);
        cardHeader.classList.add('badge', `badge-${state}`);
        cardHeader.textContent = state[0].toUpperCase() + state.slice(1);
    }

    window.updateVersionInformation = function (transmorpherIdentifier, modal) {
        let versionList = modal.querySelector('.versionList');
        let currentVersion = modal.querySelector('.currentVersion');

        versionList.textContent = '';

        fetch(motifs[transmorpherIdentifier].routes.getVersions, {
            method: 'GET', headers: {
                'Content-Type': 'application/json',
            },
        }).then(response => {
            return response.json();
        }).then(versionInformation => {
            currentVersion.textContent = versionInformation.currentVersion;

            for (const [version, timestamp] of Object.entries(versionInformation.versions ?? [])) {
                let li = document.createElement('li');
                let div = document.createElement('div');
                let button = document.createElement('button');
                let span = document.createElement('span');

                span.textContent = `${version}: ${new Date(timestamp * 1000).toDateString()}`;
                button.textContent = 'set';
                button.classList.add('badge', 'badge-processing');
                button.onclick = function () {
                    setVersion(transmorpherIdentifier, version, modal)
                }

                div.append(span, button)
                li.appendChild(div)
                versionList.appendChild(li);
            }
        });

    }

    window.setVersion = function (transmorpherIdentifier, version, modal) {
        fetch(motifs[transmorpherIdentifier].routes.setVersion, {
            method: 'POST', headers: {
                'Content-Type': 'application/json', 'X-CSRF-Token': motifs[transmorpherIdentifier].csrfToken
            }, body: JSON.stringify({
                version: version,
            })
        }).then(response => {
            return response.json();
        }).then(data => {
            updateVersionInformation(transmorpherIdentifier, modal);
            updateImageDisplay(transmorpherIdentifier, data.public_path, 'h-150', data.version);
        });
    }

    window.closeModal = function (closeButton) {
        let modal = closeButton.closest('.modal');
        let overlay = modal.nextElementSibling;

        modal.classList.add('d-none');
        overlay.classList.add('d-none');
    }

    window.openModal = function (transmorpherIdentifier) {
        let modal = document.querySelector(`#modal-${transmorpherIdentifier}`);
        let overlay = modal.nextElementSibling;

        modal.classList.remove('d-none');
        overlay.classList.remove('d-none');

        updateVersionInformation(transmorpherIdentifier, modal);
    }

    window.deleteTransmorpherMedia = function (transmorpherIdentifier) {
        fetch(motifs[transmorpherIdentifier].routes.delete, {
            method: 'POST', headers: {
                'Content-Type': 'application/json', 'X-CSRF-Token': motifs[transmorpherIdentifier].csrfToken
            },
        }).then(response => {
            return response.json();
        }).then(data => {
            updateVersionInformation(transmorpherIdentifier, document.querySelector(`#modal-${transmorpherIdentifier}`));
            updateImageDisplay(transmorpherIdentifier, null, null, null, true);
        });
    }

    window.updateImageDisplay = function (transmorpherIdentifier, path, transformations, version, placeholder = false) {
        let imgElement;

        if (imgElement = document.querySelector(`#dz-${transmorpherIdentifier} .dz-image.image-transmorpher > img`)) {
            imgElement.src = placeholder ? imgElement.dataset.placeholderUrl : imgElement.dataset.deliveryUrl + `/${path}/${transformations}?v=${version}`;
            imgElement.closest('.card').querySelector('.details > a').href = placeholder ? imgElement.dataset.placeholderUrl : imgElement.dataset.deliveryUrl + `/${path}`;
        } else if (placeholder) {
            imgElement = document.querySelector(`#dz-${transmorpherIdentifier} > .video-transmorpher`);
            imgElement.src = imgElement.dataset.placeholderUrl;
        }
    }

    window.displayError = function (message, transmorpherIdentifier) {
        let form = document.querySelector('#dz-' + transmorpherIdentifier);
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
