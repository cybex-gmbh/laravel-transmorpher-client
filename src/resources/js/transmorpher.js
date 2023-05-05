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
            }).then(pollingInformation => {
                if (pollingInformation.state === 'success') {
                    // Processing has finished, the timer can be cleared.
                    clearInterval(window[statusPollingVariable]);
                    setStatusDisplay(transmorpherIdentifier, 'success');

                    // Display the newly processed video and update links, also hide the placeholder image.
                    let videoElement = document.querySelector(`#dz-${transmorpherIdentifier} > video.video-transmorpher`);
                    videoElement.src = pollingInformation.url;
                    videoElement.querySelector('a').href = pollingInformation.url;
                    videoElement.style.display = 'block';
                    document.querySelector(`#dz-${transmorpherIdentifier} > img.video-transmorpher`).style.display = 'none';
                } else if (pollingInformation.state !== 'processing') {
                    // There was either an error or the upload slot was overwritten by another upload.
                    clearInterval(window[statusPollingVariable]);
                    setStatusDisplay(transmorpherIdentifier, 'error');
                    displayError(pollingInformation.response, transmorpherIdentifier);
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
        }).then(uploadResult => {
            handleDropzoneResult(uploadResult, transmorpherIdentifier, uploadToken);
        });
    }

    window.handleDropzoneResult = function (uploadResult, transmorpherIdentifier, uploadToken) {
        let form = document.querySelector(`#dz-${transmorpherIdentifier}`);

        if (uploadResult.success) {
            form.classList.remove('dz-started');

            if (!form.querySelector('div.dz-image.image-transmorpher > img')) {
                // It's a video dropzone, indicate that it is now processing and start polling for updates.
                setStatusDisplay(transmorpherIdentifier, 'processing');
                startPolling(transmorpherIdentifier, uploadToken)
            } else {
                // It's an image dropzone, indicate success.
                setStatusDisplay(transmorpherIdentifier, 'success');
            }
        } else {
            // There was an error.
            setStatusDisplay(transmorpherIdentifier, 'error');
            displayError(uploadResult.response, transmorpherIdentifier);
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
        let versionList = modal.querySelector('.versionList > ul');
        let currentVersion = modal.querySelector('.currentVersion');

        // Clear the list of versions.
        versionList.replaceChildren();

        // Get all versions for this media.
        fetch(motifs[transmorpherIdentifier].routes.getVersions, {
            method: 'GET', headers: {
                'Content-Type': 'application/json',
            },
        }).then(response => {
            return response.json();
        }).then(versionInformation => {
            currentVersion.textContent = versionInformation.currentVersion;

            // Add elements to display each version.
            Object.keys(versionInformation.versions ?? []).reverse().forEach(version => {
                let versionEntry = document.createElement('li');
                let controls = document.createElement('div');
                let setVersionButton = document.createElement('button');
                let versionData = document.createElement('span');
                let linkToOriginalImage = document.createElement('a');

                if (document.querySelector(`#dz-${transmorpherIdentifier} .dz-image.image-transmorpher > img`)) {
                    linkToOriginalImage.href = motifs[transmorpherIdentifier].routes.getOriginal + `/${version}`;
                    linkToOriginalImage.target = '_blank';
                    linkToOriginalImage.append(modal.previousElementSibling.querySelector('.details > a > img').cloneNode());
                }

                versionData.textContent = `${version}: ${new Date(versionInformation.versions[version] * 1000).toDateString()}`;
                setVersionButton.textContent = 'restore';
                setVersionButton.onclick = () => setVersion(transmorpherIdentifier, version, modal);

                controls.append(linkToOriginalImage, setVersionButton)
                versionEntry.append(versionData, controls)
                versionList.append(versionEntry);
            })
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
        }).then(setVersionResult => {
            updateVersionInformation(transmorpherIdentifier, modal);
            updateImageDisplay(transmorpherIdentifier, setVersionResult.public_path, 'h-150', setVersionResult.version);
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

        // Update version information when the modal is opened.
        updateVersionInformation(transmorpherIdentifier, modal);
    }

    window.deleteTransmorpherMedia = function (transmorpherIdentifier) {
        fetch(motifs[transmorpherIdentifier].routes.delete, {
            method: 'POST', headers: {
                'Content-Type': 'application/json', 'X-CSRF-Token': motifs[transmorpherIdentifier].csrfToken
            },
        }).then(response => {
            return response.json();
        }).then(deleteResult => {
            updateVersionInformation(transmorpherIdentifier, document.querySelector(`#modal-${transmorpherIdentifier}`));
            updateImageDisplay(transmorpherIdentifier, null, null, null, true);
        });
    }

    window.updateImageDisplay = function (transmorpherIdentifier, path, transformations, version, placeholder = false) {
        let imgElement;

        if (imgElement = document.querySelector(`#dz-${transmorpherIdentifier} .dz-image.image-transmorpher > img`)) {
            // It's an image dropzone, update displayed image.
            imgElement.src = placeholder ? imgElement.dataset.placeholderUrl : imgElement.dataset.deliveryUrl + `/${path}/${transformations}?v=${version}`;
            imgElement.closest('.card').querySelector('.details > a').href = placeholder ? imgElement.dataset.placeholderUrl : imgElement.dataset.deliveryUrl + `/${path}`;
        } else if (placeholder) {
            // It's a video dropzone and the media was deleted, set placeholder as displayed image.
            imgElement = document.querySelector(`#dz-${transmorpherIdentifier} > img.video-transmorpher`);
            imgElement.src = imgElement.dataset.placeholderUrl;
            imgElement.style.display = 'block';
            document.querySelector(`#dz-${transmorpherIdentifier} > video.video-transmorpher`).style.display = 'none';
        }
    }

    window.displayError = function (message, transmorpherIdentifier) {
        let form = document.querySelector('#dz-' + transmorpherIdentifier);

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
