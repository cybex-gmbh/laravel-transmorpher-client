import Dropzone from 'dropzone';

if (!window.transmorpherScriptLoaded) {
    window.transmorpherScriptLoaded = true;
    window.Dropzone = Dropzone;
    window.mediaTypes = {};
    window.transformations = {};
    window.translations = {};
    window.motifs = [];

    const IMAGE = 'IMAGE';
    const VIDEO = 'VIDEO';

    window.setupComponent = function (transmorpherIdentifier) {
        Dropzone.autoDiscover = false;
        const motif = motifs[transmorpherIdentifier];

        addConfirmEventListeners(
            document.querySelector(`#modal-mi-${transmorpherIdentifier} .confirm-delete`),
            createCallbackWithArguments(deleteTransmorpherMedia, transmorpherIdentifier),
            1500
        );

        // Start polling if the video is still processing or an upload is in process.
        if (motif.isProcessing || motif.isUploading) {
            startPolling(transmorpherIdentifier, motif.latestUploadToken);
            setAgeElement(
                document.querySelector(`#modal-mi-${transmorpherIdentifier} .age`),
                getDateForDisplay(new Date(motif.lastUpdated * 1000))
            );
        }

        new Dropzone(`#dz-${transmorpherIdentifier}`, {
            url: motif.webUploadUrl,
            chunking: true,
            chunkSize: motif.chunkSize,
            maxFilesize: motif.maxFilesize,
            maxThumbnailFilesize: motif.maxThumbnailFilesize,
            timeout: 60000,
            uploadMultiple: false,
            paramName: 'file',
            uploadToken: null,
            createImageThumbnails: false,
            dictDefaultMessage: translations['drop_files_to_upload'],
            dictFileTooBig: translations['max_file_size_exceeded'],
            dictInvalidFileType: translations['invalid_file_type'],
            init: function () {
                // Processing-Event is emitted when the upload starts.
                this.on('processing', function () {
                    fetch(`${motif.routes.setUploadingState}/${this.options.uploadToken}`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-XSRF-TOKEN': getCsrfToken()
                        },
                    });

                    // Clear any potential timer to prevent running two at the same time.
                    clearInterval(window[`statusPolling${transmorpherIdentifier}`]);
                    displayState(transmorpherIdentifier, 'uploading', null, false);
                    startPolling(transmorpherIdentifier, this.options.uploadToken);
                });
            },
            accept: function (file, done) {
                // Remove previous elements to maintain a clean overlay.
                this.element.querySelector('.dz-default').style.display = 'none';

                let errorElement;
                if (errorElement = this.element.querySelector('.dz-error')) {
                    errorElement.remove();
                }

                getState(transmorpherIdentifier)
                    .then(uploadingStateResponse => {
                        if (uploadingStateResponse.state === 'uploading' || uploadingStateResponse.state === 'processing') {
                            openUploadConfirmModal(
                                transmorpherIdentifier,
                                createCallbackWithArguments(reserveUploadSlot, transmorpherIdentifier, done)
                            );
                        } else {
                            reserveUploadSlot(transmorpherIdentifier, done);
                        }
                    })
            },
            // Update database when upload was canceled manually.
            canceled: function (file) {
                fetch(`${motifs[transmorpherIdentifier].routes.handleUploadResponse}/${this.options.uploadToken}`, {
                    method: 'POST', headers: {
                        'Content-Type': 'application/json', 'X-XSRF-TOKEN': getCsrfToken(),
                    }, body: JSON.stringify({
                        response: {
                            success: false,
                            clientMessage: translations['upload_canceled'],
                            serverResponse: this.options.dictUploadCanceled,
                        },
                        http_code: file.xhr?.status
                    })
                })
            },
            success: function (file, response) {
                this.element.querySelector('.dz-default').style.display = 'block';

                handleUploadResponse(
                    file,
                    response,
                    transmorpherIdentifier,
                    this.options.uploadToken
                );
            },
            error: function (file, response) {
                handleUploadResponse(
                    file,
                    response,
                    transmorpherIdentifier,
                    this.options.uploadToken
                );
            },
        });
    }

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
                        resetAgeElement(transmorpherIdentifier);

                        // Display the newly processed media and update links, also hide the placeholder image.
                        updateMediaDisplay(transmorpherIdentifier, pollingInformation.publicPath, pollingInformation.lastUpdated, pollingInformation.thumbnailUrl);
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
                        resetAgeElement(transmorpherIdentifier);
                    }
                        break;
                    case 'uploading': {
                        displayState(transmorpherIdentifier, 'uploading', null, false);
                        setAgeElement(document.querySelector(`#modal-mi-${transmorpherIdentifier} .age`), getDateForDisplay(new Date(pollingInformation.lastUpdated)));
                    }
                        break;
                    case 'processing': {
                        displayState(transmorpherIdentifier, 'processing', null, false);
                        setAgeElement(document.querySelector(`#modal-mi-${transmorpherIdentifier} .age`), getDateForDisplay(new Date(pollingInformation.lastUpdated)));
                    }
                        break;
                }
            })
        }, 5000); // Poll every 5 seconds
    }

    window.setAgeElement = function (ageElement, dateTime) {
        ageElement.textContent = dateTime;
        ageElement.closest('p').classList.remove('d-none');
    }

    window.resetAgeElement = function (transmorpherIdentifier) {
        document.querySelector(`#modal-mi-${transmorpherIdentifier} .age`)?.closest('p')?.classList.add('d-none')
    }

    window.handleUploadResponse = function (file, response, transmorpherIdentifier, uploadToken) {
        // Clear the old timer.
        clearInterval(window[`statusPolling${transmorpherIdentifier}`]);
        document.querySelector(`#dz-${transmorpherIdentifier}`).querySelector('.dz-preview')?.remove();

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
        resetAgeElement(transmorpherIdentifier);

        if (uploadResult.success) {
            document.querySelector(`#dz-${transmorpherIdentifier}`).classList.remove('dz-started');
            document.querySelector(`#modal-mi-${transmorpherIdentifier} .card-side .confirm-delete`).classList.remove('d-hidden');
            updateVersionInformation(transmorpherIdentifier);

            switch (motifs[transmorpherIdentifier].mediaType) {
                case mediaTypes[IMAGE]:
                    // Set the newly uploaded image as display image.
                    updateImageDisplay(transmorpherIdentifier,
                        uploadResult.public_path,
                        uploadResult.version
                    );
                    displayState(transmorpherIdentifier, 'success');
                    break;
                case mediaTypes[VIDEO]:
                    displayState(transmorpherIdentifier, 'processing');
                    startPolling(transmorpherIdentifier, uploadToken)
                    break;
            }
        } else {
            // There was an error. When the file was not accepted, e.g. due to a too large file size, the uploadResult only contains a string.
            displayState(transmorpherIdentifier, 'error', uploadResult.clientMessage ?? uploadResult);

            // Start polling for updates when the upload was aborted due to another upload.
            if (uploadResult.httpCode === 404) {
                clearInterval(window[`statusPolling${transmorpherIdentifier}`]);
                startPolling(transmorpherIdentifier, uploadResult.latestUploadToken);
                displayState(transmorpherIdentifier, 'uploading');
            }
        }

        // Reset the upload token to prevent issues with further uploads.
        document.querySelector(`#dz-${transmorpherIdentifier}`).dropzone.options.uploadToken = null;
    }

    window.updateVersionInformation = function (transmorpherIdentifier) {
        let modal = document.querySelector(`#modal-mi-${transmorpherIdentifier}`);
        let versionList = modal.querySelector('.version-list > ul');
        let defaultVersionEntry = versionList.querySelector('.version-entry').cloneNode(true);

        // Clear the list of versions.
        versionList.replaceChildren();

        // We will always need an entry to be able to clone it, even when everything is deleted.
        versionList.append(defaultVersionEntry);

        // Get all versions for this media.
        fetch(motifs[transmorpherIdentifier].routes.getVersions, {
            method: 'GET', headers: {
                'Content-Type': 'application/json',
            },
        }).then(response => {
            return response.json();
        }).then(versionInformation => {
            if (!versionInformation.currentVersion) {
                displayPlaceholder(transmorpherIdentifier);
                document.querySelector(`#modal-mi-${transmorpherIdentifier} .card-side .confirm-delete`).classList.add('d-hidden');

                return;
            }

            document.querySelector(`#modal-mi-${transmorpherIdentifier} .card-side .confirm-delete`).classList.remove('d-hidden');
            getState(transmorpherIdentifier)
                .then(stateResponse => {
                    if (stateResponse.state === 'uploading' || stateResponse.state === 'processing') {
                        // Clear any potential timer to prevent running two at the same time.
                        clearInterval(window[`statusPolling${transmorpherIdentifier}`]);
                        displayState(transmorpherIdentifier, stateResponse.state);
                        startPolling(transmorpherIdentifier, stateResponse.latestUploadToken)
                    }
                })

            let versions = versionInformation.success ? versionInformation.versions : [];

            let versionAge;
            switch (motifs[transmorpherIdentifier].mediaType) {
                case mediaTypes[IMAGE]:
                    versionAge = getDateForDisplay(new Date((versions[versionInformation.currentVersion]) * 1000));
                    updateImageDisplay(transmorpherIdentifier, versionInformation.publicPath, versionInformation.currentVersion)
                    break;
                case mediaTypes[VIDEO]:
                    versionAge = getDateForDisplay(new Date((versions[versionInformation.currentlyProcessedVersion]) * 1000));
                    if (versionInformation.currentlyProcessedVersion) {
                        updateVideoDisplay(transmorpherIdentifier, versionInformation.thumbnailUrl);
                    }
                    break;
            }

            let currentVersionAgeElement = modal.querySelector('.current-version-age');
            currentVersionAgeElement.textContent = versionAge;
            currentVersionAgeElement.classList.remove('d-none');

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
                        versionEntry.querySelector('.dz-image img:first-of-type').src = `${motifs[transmorpherIdentifier].routes.getDerivativeForVersion}/${version}/w-150`;
                        versionEntry.querySelector('.dz-image img:first-of-type').srcset = `${motifs[transmorpherIdentifier].routes.getDerivativeForVersion}/${version}/w-150 150w`;
                        break;
                    case mediaTypes[VIDEO]:
                        // Don't show video for now, will use thumbnails later.
                        versionEntry.querySelector('.media-preview').remove();
                }

                addConfirmEventListeners(versionEntry.querySelector('button'), createCallbackWithArguments(setVersion, transmorpherIdentifier, version), 1500);
                versionAge.textContent = getDateForDisplay(new Date(versions[version] * 1000));

                versionList.append(versionEntry);
                versionEntry.classList.remove('d-none');
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
                            setVersionResult.public_path,
                            setVersionResult.version
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

                document.querySelector(`#dz-${transmorpherIdentifier}`).closest('.card').querySelector('.badge').classList.add('d-hidden');
                document.querySelector(`#modal-mi-${transmorpherIdentifier} .card-side .confirm-delete`).classList.add('d-hidden');
            } else {
                clearInterval(window[`statusPolling${transmorpherIdentifier}`]);
                displayModalState(transmorpherIdentifier, 'error', deleteResult.clientMessage);
            }
        })
    }

    window.updateMediaDisplay = function (transmorpherIdentifier, publicPath, cacheKiller, videoUrl = null) {
        switch (motifs[transmorpherIdentifier].mediaType) {
            case mediaTypes[IMAGE]:
                updateImageDisplay(transmorpherIdentifier, publicPath, cacheKiller);
                break;
            case mediaTypes[VIDEO]:
                updateVideoDisplay(transmorpherIdentifier, videoUrl);
                break;
        }
    }

    window.updateImageDisplay = function (transmorpherIdentifier, publicPath, cacheKiller) {
        let imgElements = document.querySelectorAll(`#component-${transmorpherIdentifier} .dz-image.image-transmorpher > img:first-of-type`)

        imgElements.forEach(image => {
            image.src = getImageThumbnailUrl(transmorpherIdentifier, publicPath, transformations['300w'], cacheKiller);
            image.srcset = getSrcSetString(transmorpherIdentifier, publicPath, cacheKiller);
            image.closest('.full-size-link').href = getFullsizeUrl(transmorpherIdentifier, publicPath, cacheKiller);

            // Show enlarge icon.
            image.nextElementSibling.classList.remove('d-hidden');
        });
    }

    window.getSrcSetString = function (transmorpherIdentifier, publicPath, cacheKiller) {
        let srcStrings = []

        Object.keys(transformations).forEach(key => {
            srcStrings.push(`${getImageThumbnailUrl(transmorpherIdentifier, publicPath, transformations[key], cacheKiller)} ${key}`);
        })

        return srcStrings.join(', ');
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
                imgElements.forEach(image => {
                    image.closest('.full-size-link').href = image.dataset.placeholderUrl;

                    // Hide enlarge icon.
                    image.nextElementSibling.classList.add('d-hidden');
                });
                break;
            case mediaTypes[VIDEO]:
                imgElements = document.querySelectorAll(`#component-${transmorpherIdentifier} img.video-transmorpher`);
                document.querySelectorAll(`#component-${transmorpherIdentifier} video.video-transmorpher`)
                    .forEach(video => video.classList.add('d-none'));
                break;
        }

        imgElements.forEach(image => {
            image.src = image.dataset.placeholderUrl;
            image.srcset = '';
            image.classList.remove('d-none');
        })

        document.querySelector(`#modal-mi-${transmorpherIdentifier} .current-version-age`).classList.add('d-none');
    }

    window.getImageThumbnailUrl = function (transmorpherIdentifier, path, transformations, cacheKiller) {
        let imgElement = document.querySelector(`#dz-${transmorpherIdentifier} .dz-image.image-transmorpher > img`)

        return `${imgElement.dataset.deliveryUrl}/${path}/${transformations}?c=${cacheKiller}`;
    }

    window.getFullsizeUrl = function (transmorpherIdentifier, path, cacheKiller) {
        let imgElement = document.querySelector(`#dz-${transmorpherIdentifier} .dz-image.image-transmorpher > img`);

        return `${imgElement.dataset.deliveryUrl}/${path}?c=${cacheKiller}`;
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
        displayStateInformation(document.querySelector(`#modal-mi-${transmorpherIdentifier} .card-side .badge`), state);

        if (message) {
            setModalErrorMessage(transmorpherIdentifier, message);
        } else if (resetError) {
            resetModalErrorMessageDisplay(transmorpherIdentifier, message);
        }
    }

    window.displayStateInformation = function (stateInfoElement, state) {
        stateInfoElement.className = '';
        stateInfoElement.classList.add('badge', `badge-${state}`);
        stateInfoElement.querySelector('span:first-of-type').textContent = translations[state];
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
    }

    window.setModalErrorMessage = function (transmorpherIdentifier, message) {
        document.querySelector(`#modal-mi-${transmorpherIdentifier} .error-message`).textContent = message;
    }

    window.resetModalErrorMessageDisplay = function (transmorpherIdentifier) {
        document.querySelector(`#modal-mi-${transmorpherIdentifier} .error-message`).textContent = '';
    }

    window.getDateForDisplay = function (date) {
        if (isNaN(date)) {
            return '';
        }

        return date.toLocaleString();
    }

    window.openUploadConfirmModal = function (transmorpherIdentifier, callback) {
        let modal = document.querySelector(`#modal-uc-${transmorpherIdentifier}`);
        let dropzone = document.querySelector(`#dz-${transmorpherIdentifier}`).dropzone;
        let previewElement = document.querySelector(`#dz-${transmorpherIdentifier} .dz-preview ~ .dz-preview`);

        modal.classList.add('d-flex');
        previewElement ? previewElement.style.display = 'none' : null;

        modal.querySelector('.badge-error').onclick = function () {
            if (dropzone.files[1] != null) {
                dropzone.removeFile(dropzone.files[0]);
            }

            previewElement ? previewElement.style.display = 'block' : null;
            document.querySelector(`#modal-uc-${transmorpherIdentifier}`).classList.remove('d-flex');
            callback();
        }
    }

    window.closeUploadConfirmModal = function (transmorpherIdentifier) {
        document.querySelector(`#modal-uc-${transmorpherIdentifier}`).classList.remove('d-flex');
        document.querySelector(`#dz-${transmorpherIdentifier} .dz-preview ~ .dz-preview`)?.remove();
        if (document.querySelector(`#dz-${transmorpherIdentifier} .dz-preview:not(.dz-processing)`)) {
            document.querySelector(`#dz-${transmorpherIdentifier} .dz-preview`).remove();
            document.querySelector(`#dz-${transmorpherIdentifier} .dz-default`).style.display = 'block';
        }

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

    window.closeErrorMessage = function (closeButton, transmorpherIdentifier) {
        closeButton.closest('.error-display').classList.add('d-none');

        // Reset errors.
        resetModalErrorMessageDisplay(transmorpherIdentifier);
        document.querySelector(`#modal-mi-${transmorpherIdentifier} .card-side .badge.badge-error`)?.classList.add('d-none');
        closeButton.closest('.card').querySelector('.badge.badge-error')?.classList.add('d-hidden');
        closeButton.closest('.card').classList.remove('border-error');
    }

    window.getCsrfToken = function () {
        // Cookie is encoded in base64 and '=' will be URL encoded, therefore we need to decode it.
        return decodeURIComponent(document.cookie
            .split("; ")
            .find(cookie => cookie.startsWith("XSRF-TOKEN="))
            ?.split("=")[1]
        );
    }

    window.addConfirmEventListeners = function (button, callback) {
        let pressedOnce = false;
        let buttonText = button.textContent;
        let timeOut;

        button.addEventListener('pointerdown', event => {
            if (pressedOnce) {
                callback()
                button.querySelector('span').textContent = buttonText;
                pressedOnce = false;
                clearTimeout(timeOut);
            } else {
                button.querySelector('span').textContent = translations['press_again_to_confirm'];
                pressedOnce = true;
                timeOut = setTimeout(() => {
                    pressedOnce = false;
                    button.querySelector('span').textContent = buttonText;
                }, 3000);
            }
        });
    }
}
