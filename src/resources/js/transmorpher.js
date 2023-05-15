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
            fetch(motifs[transmorpherIdentifier].routes.processingState, {
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
                    setStateDisplays(transmorpherIdentifier, 'success');

                    // Display the newly processed video and update links, also hide the placeholder image.
                    updateVideoDisplay(transmorpherIdentifier, pollingInformation.url)
                    updateVersionInformation(transmorpherIdentifier)
                } else if (pollingInformation.state !== 'processing') {
                    // There was either an error or the upload slot was overwritten by another upload.
                    clearInterval(window[statusPollingVariable]);
                    setStateDisplays(transmorpherIdentifier, 'error', pollingInformation.clientMessage);
                }
            })
        }, 5000); // Poll every 5 seconds
    }

    window.handleUploadResponse = function (file, response, transmorpherIdentifier, uploadToken) {
        fetch(motifs[transmorpherIdentifier].routes.handleUploadResponse, {
            method: 'POST', headers: {
                'Content-Type': 'application/json', 'X-CSRF-Token': motifs[transmorpherIdentifier].csrfToken,
            }, body: JSON.stringify({
                transmorpher_media_key: motifs[transmorpherIdentifier].transmorpherMediaKey, upload_token: uploadToken, response: response, http_code: file.xhr?.status ?? response?.http_code
            })
        }).then(response => {
            return response.json();
        }).then(uploadResult => {
            handleDropzoneResult(uploadResult, transmorpherIdentifier, uploadToken);
        });
    }

    window.handleDropzoneResult = function (uploadResult, transmorpherIdentifier, uploadToken) {
        if (uploadResult.success) {
            document.querySelector(`#dz-${transmorpherIdentifier}`).classList.remove('dz-started');

            if (motifs[transmorpherIdentifier].isImage) {
                // It's an image dropzone, indicate success.
                setStateDisplays(transmorpherIdentifier, 'success');
            } else {
                // It's a video dropzone, indicate that it is now processing and start polling for updates.
                setStateDisplays(transmorpherIdentifier, 'processing');
                startPolling(transmorpherIdentifier, uploadToken)
            }
        } else {
            // There was an error.
            setStateDisplays(transmorpherIdentifier, 'error', uploadResult.clientMessage);
        }
    }

    window.updateVersionInformation = function (transmorpherIdentifier) {
        let modal = document.querySelector(`#modal-${transmorpherIdentifier}`);
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
        getUploadingState(transmorpherIdentifier).then(uploadingStateResponse => {
            if (uploadingStateResponse) {
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
                    updateImageDisplay(transmorpherIdentifier, setVersionResult.public_path, 'h-150', setVersionResult.version);
                } else {
                    startPolling(transmorpherIdentifier, setVersionResult.upload_token);
                }

                setStateDisplays(transmorpherIdentifier, motifs[transmorpherIdentifier].isImage ? 'success' : 'processing');
            } else {
                clearInterval(window[`statusPolling${transmorpherIdentifier}`]);
                setModalStateDisplay(transmorpherIdentifier, 'error', setVersionResult.clientMessage);
            }
        });
    }

    window.closeModal = function (transmorpherIdentifier) {
        document.querySelector(`#modal-${transmorpherIdentifier}`).classList.add('d-none');
        closeDeleteModal(transmorpherIdentifier);
    }

    window.openModal = function (transmorpherIdentifier) {
        document.querySelector(`#modal-${transmorpherIdentifier}`).classList.remove('d-none');

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
                setModalStateDisplay(transmorpherIdentifier, 'success')
                setCardBorderDisplay(transmorpherIdentifier, 'processing')
                updateVersionInformation(transmorpherIdentifier);
                updateImageDisplay(transmorpherIdentifier, null, null, null, true);

                // Hide processing display after deletion.
                document.querySelector(`#dz-${transmorpherIdentifier}`).closest('.card').querySelector('.badge').classList.add('d-hidden');
            } else {
                clearInterval(window[`statusPolling${transmorpherIdentifier}`]);
                setModalStateDisplay(transmorpherIdentifier, 'error', deleteResult.clientMessage);
            }

            // Hide delete modal.
            document.querySelector(`#delete-${transmorpherIdentifier}`).classList.add('d-none');
        })
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
            imgElement.classList.remove('d-none');
            document.querySelector(`#dz-${transmorpherIdentifier} > video.video-transmorpher`).classList.add('d-none');
        }
    }

    window.updateVideoDisplay = function (transmorpherIdentifier, url) {
        let videoElement = document.querySelector(`#dz-${transmorpherIdentifier} > video.video-transmorpher`);

        videoElement.src = url;
        videoElement.querySelector('a').href = url;
        videoElement.classList.remove('d-none');

        // Hide placeholder image.
        document.querySelector(`#dz-${transmorpherIdentifier} > img.video-transmorpher`).classList.add('d-none');
    }

    window.showDeleteModal = function (transmorpherIdentifier) {
        document.querySelector(`#delete-${transmorpherIdentifier}`).classList.remove('d-none');
    }

    window.closeDeleteModal = function (transmorpherIdentifier) {
        document.querySelector(`#delete-${transmorpherIdentifier}`).classList.add('d-none');
    }

    window.setStateDisplays = function (transmorpherIdentifier, state, message = null) {
        setDropzoneStateDisplay(transmorpherIdentifier, state, message);
        setModalStateDisplay(transmorpherIdentifier, state, message);
    }

    window.setDropzoneStateDisplay = function (transmorpherIdentifier, state, message = null) {
        let stateInfo = document.querySelector(`#dz-${transmorpherIdentifier}`).closest('.card').querySelector('.badge');

        setCardBorderDisplay(transmorpherIdentifier, state)
        setStateInfoDisplay(stateInfo, state);

        if (message) {
            setDropzoneErrorMessage(transmorpherIdentifier, message);
        } else {
            resetModalErrorMessageDisplay(transmorpherIdentifier, message);
        }
    }

    window.setModalStateDisplay = function (transmorpherIdentifier, state, message = null) {
        setStateInfoDisplay(document.querySelector(`#modal-${transmorpherIdentifier} .card-header > span`), state);

        if (message) {
            setModalErrorMessage(transmorpherIdentifier, message);
        } else {
            resetModalErrorMessageDisplay(transmorpherIdentifier, message);
        }
    }

    window.setStateInfoDisplay = function (stateInfoElement, state) {
        stateInfoElement.className = '';
        stateInfoElement.classList.add('badge', `badge-${state}`);
        stateInfoElement.textContent = state[0].toUpperCase() + state.slice(1);
    }

    window.setCardBorderDisplay = function (transmorpherIdentifier, state) {
        let card = document.querySelector(`#dz-${transmorpherIdentifier}`).closest('.card');
        card.className = '';
        card.classList.add('card', `border-${state}`);
    }

    window.setDropzoneErrorMessage = function (transmorpherIdentifier, message) {
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

    window.setModalErrorMessage = function (transmorpherIdentifier, message) {
        document.querySelector(`#modal-${transmorpherIdentifier} .delete-and-error > span`).textContent = message;
    }

    window.resetModalErrorMessageDisplay = function (transmorpherIdentifier, message) {
        document.querySelector(`#modal-${transmorpherIdentifier} .delete-and-error > span`).textContent = '';
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
    }

    window.reserveUploadSlot = function (transmorpherIdentifier, done) {
        // Reserve an upload slot at the Transmorpher media server.
        fetch(motifs[transmorpherIdentifier].routes.uploadToken, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': motifs[transmorpherIdentifier].csrfToken,
            },
            body: JSON.stringify({
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

    window.getUploadingState = function (transmorpherIdentifier) {
        return fetch(motifs[transmorpherIdentifier].routes.uploadingState, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
            }
        }).then(response => {
            return response.json();
        }).then(uploadingStateResponse => {
            return uploadingStateResponse.upload_in_process;
        });
    }

    window.createCallbackWithArguments = function (func /*, 0..n args */) {
        let args = Array.prototype.slice.call(arguments, 1);

        return function() {
            let allArguments = args.concat(Array.prototype.slice.call(arguments));
            return func.apply(this, allArguments);
        };
    }
}
