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
            fetch(motifs[transmorpherIdentifier].routes.handleUploadResponse + `/${uploadToken}`, {
                method: 'POST', headers: {
                    'Content-Type': 'application/json', 'X-CSRF-Token': motifs[transmorpherIdentifier].csrfToken,
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

            if (motifs[transmorpherIdentifier].isImage) {
                // It's an image dropzone, indicate success.
                displayState(transmorpherIdentifier, 'success');
            } else {
                // It's a video dropzone, indicate that it is now processing and start polling for updates.
                displayState(transmorpherIdentifier, 'processing');
                startPolling(transmorpherIdentifier, uploadToken)
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

            if (!motifs[transmorpherIdentifier].isImage) {
                modal.querySelector('.processed-version').textContent = versionInformation.currentlyProcessedVersion || 'none';
                modal.querySelector('.processed-version-age').textContent = timeAgo(new Date((versions[versionInformation.currentlyProcessedVersion]) * 1000)) || 'never';
            }

            // Add elements to display each version.
            Object.keys(versions).reverse().forEach(version => {
                // Don't show the currently processed or current version.
                if (version == versionInformation.currentlyProcessedVersion || version == versionInformation.currentVersion) {
                    return;
                }

                let versionEntry = document.createElement('li');
                let controls = document.createElement('div');
                let setVersionButton = document.createElement('button');
                let versionData = document.createElement('span');
                let linkToOriginalImage = document.createElement('a');

                if (motifs[transmorpherIdentifier].isImage) {
                    linkToOriginalImage.href = motifs[transmorpherIdentifier].routes.getOriginal + `/${version}`;
                    linkToOriginalImage.target = '_blank';
                    linkToOriginalImage.append(modal.previousElementSibling.querySelector('.details > a > img').cloneNode());
                }

                versionData.textContent = `${version}: ${timeAgo(new Date(versions[version] * 1000))}`;
                setVersionButton.textContent = 'restore';
                setVersionButton.onclick = () => setVersion(transmorpherIdentifier, version, modal);

                controls.append(linkToOriginalImage, setVersionButton)
                versionEntry.append(versionData, controls)
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
                'Content-Type': 'application/json', 'X-CSRF-Token': motifs[transmorpherIdentifier].csrfToken
            }, body: JSON.stringify({
                version: version,
            })
        }).then(response => {
            return response.json();
        }).then(setVersionResult => {
            if (setVersionResult.success) {
                clearInterval(window[`statusPolling${transmorpherIdentifier}`]);
                updateVersionInformation(transmorpherIdentifier);

                if (motifs[transmorpherIdentifier].isImage) {
                    updateMediaDisplay(
                        transmorpherIdentifier,
                        getImageThumbnailUrl(transmorpherIdentifier, setVersionResult.public_path, 'h-150', setVersionResult.version),
                        getFullsizeUrl(transmorpherIdentifier, setVersionResult.public_path, setVersionResult.version)
                    );
                } else {
                    startPolling(transmorpherIdentifier, setVersionResult.upload_token);
                }

                displayState(transmorpherIdentifier, motifs[transmorpherIdentifier].isImage ? 'success' : 'processing');
            } else {
                clearInterval(window[`statusPolling${transmorpherIdentifier}`]);
                displayModalState(transmorpherIdentifier, 'error', setVersionResult.clientMessage);
            }
        });
    }

    window.closeMoreInformationModal = function (transmorpherIdentifier) {
        document.querySelector(`#modal-mi-${transmorpherIdentifier}`).classList.add('d-none');
        closeDeleteModal(transmorpherIdentifier);
    }

    window.openMoreInformationModal = function (transmorpherIdentifier) {
        document.querySelector(`#modal-mi-${transmorpherIdentifier}`).classList.remove('d-none');

        // Update version information when the modal is opened.
        updateVersionInformation(transmorpherIdentifier);
    }

    window.deleteTransmorpherMedia = function (transmorpherIdentifier) {
        fetch(motifs[transmorpherIdentifier].routes.delete, {
            method: 'POST', headers: {
                'Content-Type': 'application/json', 'X-CSRF-Token': motifs[transmorpherIdentifier].csrfToken
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

            // Hide delete modal.
            document.querySelector(`#delete-${transmorpherIdentifier}`).classList.add('d-none');
        })
    }

    window.updateMediaDisplay = function (transmorpherIdentifier, thumbnailUrl, fullsizeUrl) {
        if (motifs[transmorpherIdentifier].isImage) {
            updateImageDisplay(transmorpherIdentifier, thumbnailUrl, fullsizeUrl)
        } else {
            updateVideoDisplay(transmorpherIdentifier, thumbnailUrl)
        }
    }

    window.updateImageDisplay = function (transmorpherIdentifier, thumbnailUrl, fullsizeUrl) {
        let imgElement = document.querySelector(`#dz-${transmorpherIdentifier} .dz-image.image-transmorpher > img`)
        imgElement.src = thumbnailUrl
        imgElement.closest('.card').querySelector('.details > a').href = fullsizeUrl;
    }


    window.updateVideoDisplay = function (transmorpherIdentifier, url) {
        let videoElement = document.querySelector(`#dz-${transmorpherIdentifier} > video.video-transmorpher`);

        videoElement.src = url;
        videoElement.querySelector('a').href = url;
        videoElement.classList.remove('d-none');

        // Hide placeholder image.
        document.querySelector(`#dz-${transmorpherIdentifier} > img.video-transmorpher`).classList.add('d-none');
    }

    window.displayPlaceholder = function (transmorpherIdentifier) {
        let imgElement;

        if (motifs[transmorpherIdentifier].isImage) {
            imgElement = document.querySelector(`#dz-${transmorpherIdentifier} .dz-image.image-transmorpher > img`)
            imgElement.closest('.card').querySelector('.details > a').href = imgElement.dataset.placeholderUrl;
        } else {
            imgElement = document.querySelector(`#dz-${transmorpherIdentifier} > img.video-transmorpher`);
            document.querySelector(`#dz-${transmorpherIdentifier} > video.video-transmorpher`).classList.add('d-none');
        }

        imgElement.src = imgElement.dataset.placeholderUrl;
        imgElement.classList.remove('d-none');
    }

    window.getImageThumbnailUrl = function (transmorpherIdentifier, path, transformations, version) {
        let imgElement = document.querySelector(`#dz-${transmorpherIdentifier} .dz-image.image-transmorpher > img`)

        return imgElement.dataset.deliveryUrl + `/${path}/${transformations}?v=${version}`;
    }

    window.getFullsizeUrl = function (transmorpherIdentifier, path, version) {
        let imgElement = document.querySelector(`#dz-${transmorpherIdentifier} .dz-image.image-transmorpher > img`);

        return imgElement.dataset.deliveryUrl + `/${path}?v=${version}`;
    }

    window.showDeleteModal = function (transmorpherIdentifier) {
        document.querySelector(`#delete-${transmorpherIdentifier}`).classList.remove('d-none');
    }

    window.closeDeleteModal = function (transmorpherIdentifier) {
        document.querySelector(`#delete-${transmorpherIdentifier}`).classList.add('d-none');
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
        stateInfoElement.textContent = state[0].toUpperCase() + state.slice(1);
    }

    window.displayCardBorderState = function (transmorpherIdentifier, state) {
        let card = document.querySelector(`#dz-${transmorpherIdentifier}`).closest('.card');
        card.className = '';
        card.classList.add('card', `border-${state}`);
    }

    window.displayDropzoneErrorMessage = function (transmorpherIdentifier, message) {
        let form = document.querySelector('#dz-' + transmorpherIdentifier);

        // Add preview element, which also displays errors, when it is not present yet.
        if (!form.querySelector('.dz-preview')) {
            form.innerHTML = form.innerHTML + form.dropzone.options.previewTemplate;
        }

        let errorMessage = form.querySelector('.dz-error-message')
        errorMessage.replaceChildren();
        errorMessage.append(message);
        errorMessage.style.display = 'block';

        let previewElement = form.querySelector('.dz-preview');
        previewElement.classList.add('dz-error');
        previewElement.style.display = 'block';

        // Remove visual clutter.
        form.querySelector('.dz-default').style.display = 'none';
        form.querySelector('.dz-progress').style.display = 'none';
        form.querySelector('.dz-details').style.display = 'none';
    }

    window.setModalErrorMessage = function (transmorpherIdentifier, message) {
        document.querySelector(`#modal-mi-${transmorpherIdentifier} .delete-and-error > span`).textContent = message;
    }

    window.resetModalErrorMessageDisplay = function (transmorpherIdentifier) {
        document.querySelector(`#modal-mi-${transmorpherIdentifier} .delete-and-error > span`).textContent = '';
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
            return interval + ' years ago';
        }

        interval = Math.floor(seconds / 2592000);
        if (interval > 1) {
            return interval + ' months ago';
        }

        interval = Math.floor(seconds / 86400);
        if (interval > 1) {
            return interval + ' days ago';
        }

        interval = Math.floor(seconds / 3600);
        if (interval > 1) {
            return interval + ' hours ago';
        }

        interval = Math.floor(seconds / 60);
        if (interval > 1) {
            return interval + ' minutes ago';
        }

        if (seconds < 10) return 'just now';

        return Math.floor(seconds) + ' seconds ago';
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
                'Content-Type': 'application/json', 'X-CSRF-Token': motifs[transmorpherIdentifier].csrfToken,
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
            dropzone.options.url = motifs[transmorpherIdentifier].webUploadUrl + getUploadTokenResult.upload_token;

            done()
        });
    }

    window.getState = function (transmorpherIdentifier, uploadToken = null) {
        return fetch(motifs[transmorpherIdentifier].routes.state, {
            method: 'POST', headers: {
                'Content-Type': 'application/json', 'X-CSRF-Token': motifs[transmorpherIdentifier].csrfToken,
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
}
