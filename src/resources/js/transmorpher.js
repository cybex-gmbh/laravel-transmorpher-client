import Dropzone from 'dropzone';

if (!window.transmorpherScriptLoaded) {
    window.transmorpherScriptLoaded = true;
    window.Dropzone = Dropzone;
    window.mediaTypes = [];
    window.motifs = [];

    const IMAGE = 'IMAGE';
    const VIDEO = 'VIDEO';

    window.startPolling = function (transmorpherIdentifier, uploadToken) {
        let statusPollingVariable = `statusPolling${transmorpherIdentifier}`
        let expirationTime = new Date();
        expirationTime.setDate(expirationTime.getDate() + 1);

        // Set a timer to start polling for new information on the status of the processing video or an upload.
        // Has to be stored in a global variable, to be able to clear the timer when a new video is dropped in the dropzone.
        window[statusPollingVariable] = setInterval(function () {
            // Clear timer after 24 hours.
            if (new Date().getTime > expirationTime) {
                clearInterval(window[statusPollingVariable]);
            }

            // Poll for status updates.
            getState(transmorpherIdentifier, uploadToken).then(pollingInformation => {
                switch (pollingInformation.state) {
                    case 'success': {
                        // Processing has finished, the timer can be cleared.
                        clearInterval(window[statusPollingVariable]);
                        displayState(transmorpherIdentifier, 'success');
                        resetAgeElements(transmorpherIdentifier);

                        // Display the newly processed media and update links, also hide the placeholder image.
                        updateMediaDisplay(transmorpherIdentifier, pollingInformation.thumbnailUrl, pollingInformation.fullsizeUrl);
                        updateVersionInformation(transmorpherIdentifier);
                    }
                        break;
                    case 'error': {
                        // There was either an error or the upload slot was overwritten by another upload.
                        clearInterval(window[statusPollingVariable]);

                        // Poll for new upload when the upload slot was overwritten.
                        if (uploadToken !== pollingInformation.latestUploadToken) {
                            startPolling(transmorpherIdentifier, pollingInformation.latestUploadToken);
                        }

                        displayState(transmorpherIdentifier, 'error', pollingInformation.clientMessage);
                        resetAgeElements(transmorpherIdentifier);
                    }
                        break;
                    case 'uploading': {
                        displayState(transmorpherIdentifier, 'uploading', null, false);
                        setAgeElement(document.querySelector(`#modal-mi-${transmorpherIdentifier} .upload-age`), timeAgo(Date.parse(pollingInformation.lastUpdated)));
                    }
                        break;
                    case 'processing': {
                        displayState(transmorpherIdentifier, 'processing', null, false);
                        setAgeElement(document.querySelector(`#modal-mi-${transmorpherIdentifier} .processing-age`), timeAgo(Date.parse(pollingInformation.lastUpdated)));
                    }
                        break;
                }
            })
        }, 5000); // Poll every 5 seconds
    }

    window.setAgeElement = function (ageElement, timeAgo) {
        ageElement.textContent = timeAgo;
        ageElement.closest('p').classList.remove('d-none');
    }

    window.resetAgeElements = function (transmorpherIdentifier) {
        document.querySelector(`#modal-mi-${transmorpherIdentifier} .upload-age`).closest('p').classList.add('d-none')
        document.querySelector(`#modal-mi-${transmorpherIdentifier} .processing-age`).closest('p').classList.add('d-none')
    }

    window.handleUploadResponse = function (file, response, transmorpherIdentifier, uploadToken) {
        if (uploadToken) {
            fetch(`${motifs[transmorpherIdentifier].routes.handleUploadResponse}/${uploadToken}`, {
                method: 'POST', headers: {
                    'Content-Type': 'application/json', 'X-XSRF-TOKEN': getCsrfToken(),
                }, body: JSON.stringify({
                    // When the token retrieval failed, file doesn't contain the http code.
                    // It is instead passed in the response of the token retrieval request.
                    response: response, http_code: file.xhr?.status ?? response?.http_code
                })
            }).then(response => {
                return response.json();
            }).then(uploadResult => {
                displayUploadResult(uploadResult, transmorpherIdentifier, uploadToken);
            });
        } else {
            displayUploadResult(response, transmorpherIdentifier, uploadToken);
        }
    }

    window.displayUploadResult = function (uploadResult, transmorpherIdentifier, uploadToken) {
        resetAgeElements(transmorpherIdentifier);

        if (uploadResult.success) {
            document.querySelector(`#dz-${transmorpherIdentifier}`).classList.remove('dz-started');

            switch (motifs[transmorpherIdentifier].mediaType) {
                case mediaTypes[IMAGE]:
                    // Set the newly uploaded image as display image.
                    updateImageDisplay(transmorpherIdentifier,
                        getImageThumbnailUrl(transmorpherIdentifier, uploadResult.public_path, 'h-150', uploadResult.version),
                        getFullsizeUrl(transmorpherIdentifier, uploadResult.public_path, uploadResult.version)
                    );
                    displayState(transmorpherIdentifier, 'success');
                    break;
                case mediaTypes[VIDEO]:
                    displayState(transmorpherIdentifier, 'processing');
                    startPolling(transmorpherIdentifier, uploadToken)
                    break;
            }
        } else {
            // There was an error.
            displayState(transmorpherIdentifier, 'error', uploadResult.clientMessage);

            // Start polling for updates when the upload was aborted due to another upload.
            if (uploadResult.httpCode === 404) {
                startPolling(transmorpherIdentifier, uploadResult.latestUploadToken);
                displayState(transmorpherIdentifier, 'uploading');
            }
        }
    }

    window.updateVersionInformation = function (transmorpherIdentifier) {
        let modal = document.querySelector(`#modal-mi-${transmorpherIdentifier}`);
        let versionList = modal.querySelector('.version-list > ul');
        let defaultVersionEntry = versionList.querySelector('.version-entry').cloneNode(true);
        defaultVersionEntry.classList.remove('d-none');

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
            let versions = versionInformation.success ? versionInformation.versions : [];

            modal.querySelector('.current-version').textContent = versionInformation.currentVersion || 'none';
            modal.querySelector('.current-version-age').textContent = timeAgo(new Date((versions[versionInformation.currentVersion]) * 1000)) || 'never';

            switch (motifs[transmorpherIdentifier].mediaType) {
                case mediaTypes[VIDEO]:
                    modal.querySelector('.processed-version').textContent = versionInformation.currentlyProcessedVersion || 'none';
                    modal.querySelector('.processed-version-age').textContent = timeAgo(new Date((versions[versionInformation.currentlyProcessedVersion]) * 1000)) || 'never';
                    break;
            }

            Object.keys(versions).sort((a, b) => versions[b] - versions[a]).forEach(version => {
                // Don't show the currently processed or current version.
                if (version == versionInformation.currentlyProcessedVersion || version == versionInformation.currentVersion) {
                    return;
                }

                let versionEntry = defaultVersionEntry.cloneNode(true);
                let versionAge = versionEntry.querySelector('.version-age');

                switch (motifs[transmorpherIdentifier].mediaType) {
                    case mediaTypes[IMAGE]:
                        versionEntry.querySelector('a').href = `${motifs[transmorpherIdentifier].routes.getOriginal}/${version}`;
                        versionEntry.querySelector('.dz-image img:first-of-type').src = `${motifs[transmorpherIdentifier].routes.getOriginalDerivative}/${version}/h-150`;
                        break;
                }

                addConfirmEventListeners(versionEntry.querySelector('button'), createCallbackWithArguments(setVersion, transmorpherIdentifier, version), 'restore', 1500);
                versionAge.textContent = timeAgo(new Date(versions[version] * 1000));

                versionList.append(versionEntry);
            })
        })
    }

    window.setVersion = function (transmorpherIdentifier, version) {
        getState(transmorpherIdentifier).then(uploadingStateResponse => {
            if (uploadingStateResponse.state === 'uploading' || uploadingStateResponse.state === 'processing') {
                openUploadConfirmModal(transmorpherIdentifier, createCallbackWithArguments(makeSetVersionCall, transmorpherIdentifier, version));
            } else {
                makeSetVersionCall(transmorpherIdentifier, version);
            }
        })
    }

    window.makeSetVersionCall = function (transmorpherIdentifier, version) {
        fetch(motifs[transmorpherIdentifier].routes.setVersion, {
            method: 'POST', headers: {
                'Content-Type': 'application/json', 'X-XSRF-TOKEN': getCsrfToken()
            }, body: JSON.stringify({
                version: version,
            })
        }).then(response => {
            return response.json();
        }).then(setVersionResult => {
            if (setVersionResult.success) {
                clearInterval(window[`statusPolling${transmorpherIdentifier}`]);
                updateVersionInformation(transmorpherIdentifier);

                let state;
                switch (motifs[transmorpherIdentifier].mediaType) {
                    case mediaTypes[IMAGE]:
                        updateMediaDisplay(
                            transmorpherIdentifier,
                            getImageThumbnailUrl(transmorpherIdentifier, setVersionResult.public_path, 'h-150', setVersionResult.version),
                            getFullsizeUrl(transmorpherIdentifier, setVersionResult.public_path, setVersionResult.version)
                        );
                        state = 'success';
                        break;
                    case mediaTypes[VIDEO]:
                        startPolling(transmorpherIdentifier, setVersionResult.upload_token);
                        state = 'processing';
                        break;
                }

                displayState(transmorpherIdentifier, state);
            } else {
                clearInterval(window[`statusPolling${transmorpherIdentifier}`]);
                displayModalState(transmorpherIdentifier, 'error', setVersionResult.clientMessage);
            }
        });
    }

    window.closeMoreInformationModal = function (transmorpherIdentifier) {
        document.querySelector(`#modal-mi-${transmorpherIdentifier}`).classList.remove('d-flex');
    }

    window.openMoreInformationModal = function (transmorpherIdentifier) {
        document.querySelector(`#modal-mi-${transmorpherIdentifier}`).classList.add('d-flex');

        // Update version information when the modal is opened.
        updateVersionInformation(transmorpherIdentifier);
    }

    window.deleteTransmorpherMedia = function (transmorpherIdentifier) {
        fetch(motifs[transmorpherIdentifier].routes.delete, {
            method: 'POST', headers: {
                'Content-Type': 'application/json', 'X-XSRF-TOKEN': getCsrfToken()
            },
        }).then(response => {
            return response.json();
        }).then(deleteResult => {
            if (deleteResult.success) {
                clearInterval(window[`statusPolling${transmorpherIdentifier}`]);
                displayModalState(transmorpherIdentifier, 'success')
                displayCardBorderState(transmorpherIdentifier, 'processing')
                updateVersionInformation(transmorpherIdentifier);
                displayPlaceholder(transmorpherIdentifier);
                resetAgeElements(transmorpherIdentifier);

                // Hide processing display after deletion.
                document.querySelector(`#dz-${transmorpherIdentifier}`).closest('.card').querySelector('.badge').classList.add('d-hidden');
            } else {
                clearInterval(window[`statusPolling${transmorpherIdentifier}`]);
                displayModalState(transmorpherIdentifier, 'error', deleteResult.clientMessage);
            }
        })
    }

    window.updateMediaDisplay = function (transmorpherIdentifier, thumbnailUrl, fullsizeUrl) {
        switch (motifs[transmorpherIdentifier].mediaType) {
            case mediaTypes[IMAGE]:
                updateImageDisplay(transmorpherIdentifier, thumbnailUrl, fullsizeUrl);
                break;
            case mediaTypes[VIDEO]:
                updateVideoDisplay(transmorpherIdentifier, thumbnailUrl);
                break;
        }
    }

    window.updateImageDisplay = function (transmorpherIdentifier, thumbnailUrl, fullsizeUrl) {
        let imgElements = document.querySelectorAll(`#component-${transmorpherIdentifier} .dz-image.image-transmorpher > img:first-of-type`)

        imgElements.forEach(image => {
            image.src = thumbnailUrl
            image.closest('.full-size-link').href = fullsizeUrl;
        });
    }


    window.updateVideoDisplay = function (transmorpherIdentifier, url) {
        let videoElements = document.querySelectorAll(`#component-${transmorpherIdentifier} video.video-transmorpher`);

        videoElements.forEach(video => {
            video.src = url;
            video.querySelector('a').href = url;
            video.classList.remove('d-none');
        })

        // Hide placeholder images.
        document.querySelectorAll(`#component-${transmorpherIdentifier} img.video-transmorpher`)
            .forEach(placeholder => placeholder.classList.add('d-none'));
    }

    window.displayPlaceholder = function (transmorpherIdentifier) {
        let imgElements;

        switch (motifs[transmorpherIdentifier].mediaType) {
            case mediaTypes[IMAGE]:
                imgElements = document.querySelectorAll(`#component-${transmorpherIdentifier} .dz-image.image-transmorpher > img:first-of-type`)
                imgElements.forEach(image => image.closest('.full-size-link').href = image.dataset.placeholderUrl);
                break;
            case mediaTypes[VIDEO]:
                imgElements = document.querySelectorAll(`#component-${transmorpherIdentifier} img.video-transmorpher`);
                document.querySelectorAll(`#component-${transmorpherIdentifier} > video.video-transmorpher`)
                    .forEach(video => video.classList.add('d-none'));
                break;
        }

        imgElements.forEach(image => {
            image.src = image.dataset.placeholderUrl;
            image.classList.remove('d-none');
        })
    }

    window.getImageThumbnailUrl = function (transmorpherIdentifier, path, transformations, version) {
        let imgElement = document.querySelector(`#dz-${transmorpherIdentifier} .dz-image.image-transmorpher > img`)

        return `${imgElement.dataset.deliveryUrl}/${path}/${transformations}?v=${version}`;
    }

    window.getFullsizeUrl = function (transmorpherIdentifier, path, version) {
        let imgElement = document.querySelector(`#dz-${transmorpherIdentifier} .dz-image.image-transmorpher > img`);

        return `${imgElement.dataset.deliveryUrl}/${path}?v=${version}`;
    }

    window.displayState = function (transmorpherIdentifier, state, message = null, resetError = true) {
        displayDropzoneState(transmorpherIdentifier, state, message, resetError);
        displayModalState(transmorpherIdentifier, state, message, resetError);
    }

    window.displayDropzoneState = function (transmorpherIdentifier, state, message = null, resetError = true) {
        let stateInfo = document.querySelector(`#dz-${transmorpherIdentifier}`).closest('.card').querySelector('.badge');

        displayCardBorderState(transmorpherIdentifier, state)
        displayStateInformation(stateInfo, state);

        if (message) {
            displayDropzoneErrorMessage(transmorpherIdentifier, message);
        } else if (resetError) {
            resetModalErrorMessageDisplay(transmorpherIdentifier, message);
        }
    }

    window.displayModalState = function (transmorpherIdentifier, state, message = null, resetError = true) {
        displayStateInformation(document.querySelector(`#modal-mi-${transmorpherIdentifier} .card-header > span`), state);

        if (message) {
            setModalErrorMessage(transmorpherIdentifier, message);
        } else if (resetError) {
            resetModalErrorMessageDisplay(transmorpherIdentifier, message);
        }
    }

    window.displayStateInformation = function (stateInfoElement, state) {
        stateInfoElement.className = '';
        stateInfoElement.classList.add('badge', `badge-${state}`);
        stateInfoElement.textContent = `${state[0].toUpperCase()}${state.slice(1)}`;
    }

    window.displayCardBorderState = function (transmorpherIdentifier, state) {
        let card = document.querySelector(`#dz-${transmorpherIdentifier}`).closest('.card');
        card.className = '';
        card.classList.add('card', `border-${state}`);
    }

    window.displayDropzoneErrorMessage = function (transmorpherIdentifier, message) {
        let form = document.querySelector(`#dz-${transmorpherIdentifier}`);
        let errorDisplay = form.querySelector('.error-display');

        errorDisplay.classList.remove('d-none');
        errorDisplay.querySelector('.error-message').textContent = message
        form.querySelector('.dz-default').style.display = 'block';

        // Remove visual clutter.
        form.querySelector('.dz-preview').style.display = 'none';
        form.querySelector('.dz-progress').style.display = 'none';
        form.querySelector('.dz-details').style.display = 'none';
    }

    window.setModalErrorMessage = function (transmorpherIdentifier, message) {
        document.querySelector(`#modal-mi-${transmorpherIdentifier} .error-message`).textContent = message;
    }

    window.resetModalErrorMessageDisplay = function (transmorpherIdentifier) {
        document.querySelector(`#modal-mi-${transmorpherIdentifier} .error-message`).textContent = '';
        document.querySelector(`#dz-${transmorpherIdentifier} .dz-default`).style.display = 'block';

        let previewElement = null;
        if (previewElement = document.querySelector(`#dz-${transmorpherIdentifier} .dz-preview`)) {
            previewElement.style.display = 'none'
        }
    }

    window.timeAgo = function (date) {
        if (isNaN(date)) {
            return false;
        }

        const seconds = Math.floor((new Date() - date) / 1000);

        let interval = Math.floor(seconds / 31536000);
        if (interval > 1) {
            return `${interval} years ago`;
        }

        interval = Math.floor(seconds / 2592000);
        if (interval > 1) {
            return `${interval} months ago`;
        }

        interval = Math.floor(seconds / 86400);
        if (interval > 1) {
            return `${interval} days ago`;
        }

        interval = Math.floor(seconds / 3600);
        if (interval > 1) {
            return `${interval} hours ago`;
        }

        interval = Math.floor(seconds / 60);
        if (interval > 1) {
            return `${interval} minutes ago`;
        }

        if (seconds < 10) return 'just now';

        return `${Math.floor(seconds)} seconds ago`;
    };

    window.openUploadConfirmModal = function (transmorpherIdentifier, callback) {
        let modal = document.querySelector(`#modal-uc-${transmorpherIdentifier}`);
        modal.classList.remove('d-none');
        modal.querySelector('.badge-error').onclick = function () {
            closeUploadConfirmModal(transmorpherIdentifier);
            callback();
        }
    }

    window.closeUploadConfirmModal = function (transmorpherIdentifier) {
        document.querySelector(`#modal-uc-${transmorpherIdentifier}`).classList.add('d-none');
        document.querySelector(`#dz-${transmorpherIdentifier} .dz-default`).style.display = 'block';
        document.querySelector(`#dz-${transmorpherIdentifier} .dz-preview`)?.remove();

        getState(transmorpherIdentifier).then(stateResponse => {
            // Clear any potential timer to prevent running two at the same time.
            clearInterval(window[`statusPolling${transmorpherIdentifier}`]);
            displayState(transmorpherIdentifier, stateResponse.state);
            startPolling(transmorpherIdentifier, stateResponse.latestUploadToken)
        })
    }

    window.reserveUploadSlot = function (transmorpherIdentifier, done) {
        // Reserve an upload slot at the Transmorpher media server.
        fetch(motifs[transmorpherIdentifier].routes.uploadToken, {
            method: 'POST', headers: {
                'Content-Type': 'application/json', 'X-XSRF-TOKEN': getCsrfToken()
            }, body: JSON.stringify({
                transmorpher_media_key: motifs[transmorpherIdentifier].transmorpherMediaKey,
            }),
        }).then(response => {
            return response.json();
        }).then(getUploadTokenResult => {
            if (!getUploadTokenResult.success) {
                done(getUploadTokenResult);
            }

            let dropzone = document.querySelector(`#dz-${transmorpherIdentifier}`).dropzone;
            dropzone.options.uploadToken = getUploadTokenResult.upload_token
            // Set the dropzone target to the media server upload url, which needs a valid upload token.
            dropzone.options.url = `${motifs[transmorpherIdentifier].webUploadUrl}${getUploadTokenResult.upload_token}`;

            done()
        });
    }

    window.getState = function (transmorpherIdentifier, uploadToken = null) {
        return fetch(motifs[transmorpherIdentifier].routes.state, {
            method: 'POST', headers: {
                'Content-Type': 'application/json', 'X-XSRF-TOKEN': getCsrfToken()
            }, body: JSON.stringify({
                upload_token: uploadToken,
            }),
        }).then(response => {
            return response.json();
        })
    }

    window.createCallbackWithArguments = function (func /*, 0..n args */) {
        let args = Array.prototype.slice.call(arguments, 1);

        return function () {
            let allArguments = args.concat(Array.prototype.slice.call(arguments));
            return func.apply(this, allArguments);
        };
    }

    window.closeErrorMessage = function (closeButton) {
        closeButton.closest('.error-display').classList.add('d-none');
    }

    window.getCsrfToken = function () {
        // Cookie is encoded in base64 and '=' will be URL encoded, therefore we need to decode it.
        return decodeURIComponent(document.cookie
            .split("; ")
            .find(cookie => cookie.startsWith("XSRF-TOKEN="))
            ?.split("=")[1]
        );
    }

    window.addConfirmEventListeners = function (button, callback, buttonText, duration) {
        let pressedOnce = false;
        let defaultText = `${buttonText[0].toUpperCase()}${buttonText.slice(1)}`;

        button.addEventListener('pointerdown', event => {
            if (event.pointerType === 'touch') {
                if (pressedOnce) {
                    callback()
                    button.querySelector('span').textContent = defaultText;
                } else {
                    button.querySelector('span').textContent = `Press again to ${buttonText}`
                    pressedOnce = true;
                    setTimeout(() => {
                        pressedOnce = false;
                        button.querySelector('span').textContent = defaultText;
                    }, 1000);
                }
            } else {
                button.querySelector('span').textContent = `Hold to ${buttonText}`
                button.timeout = setTimeout(callback, duration, button);
            }
        });

        button.addEventListener('pointerup', event => {
            if (event.pointerType === 'mouse') {
                button.querySelector('span').textContent = defaultText
                clearTimeout(button.timeout);
            }
        });
    }
}
