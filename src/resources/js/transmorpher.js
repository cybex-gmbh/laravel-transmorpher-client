import Dropzone from 'dropzone';

if (!window.transmorpherScriptLoaded) {
    window.transmorpherScriptLoaded = true;
    window.Dropzone = Dropzone;
    window.mediaTypes = {};
    window.transformations = {};
    window.media = [];

    const IMAGE = 'IMAGE';
    const PDF = 'PDF';
    const VIDEO = 'VIDEO';

    window.setupComponent = function (transmorpherIdentifier) {
        Dropzone.autoDiscover = false;
        const medium = media[transmorpherIdentifier];

        addConfirmEventListener(
            document.querySelector(`#modal-mi-${transmorpherIdentifier} .confirm-delete`),
            createCallbackWithArguments(deleteTransmorpherMedia, transmorpherIdentifier),
            transmorpherIdentifier
        );

        // Start polling if the video is still processing or an upload is in process.
        if (medium.isProcessing || medium.isUploading) {
            startPolling(transmorpherIdentifier, medium.latestUploadToken);
            setAgeElement(
                document.querySelector(`#modal-mi-${transmorpherIdentifier} .age`),
                getDateForDisplay(new Date(medium.lastUpdated * 1000))
            );
        }

        new Dropzone(`#dz-${transmorpherIdentifier}`, {
            url: medium.webUploadUrl,
            acceptedFiles: medium.acceptedFileTypes,
            chunking: true,
            chunkSize: medium.chunkSize,
            maxFilesize: medium.maxFilesize,
            maxThumbnailFilesize: medium.maxThumbnailFilesize,
            timeout: 60000,
            uploadMultiple: false,
            paramName: 'file',
            uploadToken: null,
            dictDefaultMessage: medium.translations['drop_files_to_upload'],
            dictFileTooBig: medium.translations['max_file_size_exceeded'],
            dictInvalidFileType: medium.translations['invalid_file_type'],
            createImageThumbnails: false,
            init: function () {
                // Processing-Event is emitted when the upload starts.
                this.on('processing', function () {
                    fetch(`${medium.routes.setUploadingState}/${this.options.uploadToken}`, {
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

                this.on('sending', function (file, xhr, formData) {
                    // Add identifier to request body.
                    formData.append('identifier', transmorpherIdentifier);
                })
            },
            thumbnail: async function (file) {
                // Dropzone sometimes (small files) manages to calculate width and height, if not, we have to calculate it ourselves.
                if (!file.width || !file.height) {
                    let dimensions = await getMediaDimensions(file, transmorpherIdentifier).catch(error => {
                        file.done(error);
                    });

                    file.width = dimensions.width;
                    file.height = dimensions.height;
                }

                if ((medium.maxWidth && file.width > medium.maxWidth) || (medium.maxHeight && file.height > medium.maxHeight)) {
                    file.done(medium.translations['max_dimensions_exceeded']);
                } else if ((medium.minWidth && file.width < medium.minWidth) || (medium.minHeight && file.height < medium.minHeight)) {
                    file.done(medium.translations['min_dimensions_subceeded']);
                    // Since testing floating point values for equality is problematic, we define an upper bound on the rounding error.
                } else if (medium.ratio && Math.abs(file.width / file.height - medium.ratio) > 0.0000000001) {
                    file.done(medium.translations['invalid_ratio']);
                } else {
                    getState(transmorpherIdentifier)
                        .then(uploadingStateResponse => {
                            if (uploadingStateResponse.state === 'uploading' || uploadingStateResponse.state === 'processing') {
                                openUploadConfirmModal(
                                    transmorpherIdentifier,
                                    createCallbackWithArguments(reserveUploadSlot, transmorpherIdentifier, file.done),
                                );
                            } else {
                                reserveUploadSlot(transmorpherIdentifier, file.done);
                            }
                        })
                }
            },
            accept: function (file, done) {
                file.done = done;

                // Remove previous elements to maintain a clean overlay.
                this.element.querySelector('.dz-default').style.display = 'none';

                let errorElement;
                if (errorElement = this.element.querySelector('.dz-error')) {
                    errorElement.remove();
                }

                this.emit("thumbnail", file);
            },
            // Update database when upload was canceled manually.
            canceled: function (file) {
                fetch(`${medium.routes.handleUploadResponse}/${this.options.uploadToken}`, {
                    method: 'POST', headers: {
                        'Content-Type': 'application/json', 'X-XSRF-TOKEN': getCsrfToken(),
                    }, body: JSON.stringify({
                        response: {
                            state: 'error',
                            clientMessage: medium.translations['upload_canceled'],
                            message: this.options.dictUploadCanceled,
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

    window.getMediaDimensions = function (file, transmorpherIdentifier) {
        switch (media[transmorpherIdentifier].mediaType) {
            case mediaTypes[IMAGE]:
                return getImageDimensions(file);
            case mediaTypes[PDF]:
                return new Promise((resolve) => resolve({width: null, height: null}));
            case mediaTypes[VIDEO]:
                return getVideoDimensions(file)
        }
    }

    window.getImageDimensions = function (file, transmorpherIdentifier) {
        return new Promise((resolve, reject) => {
            let img = new Image();
            img.src = URL.createObjectURL(file);

            img.onload = function () {
                URL.revokeObjectURL(this.src);
                resolve({
                    width: this.width,
                    height: this.height
                })
            }

            img.onerror = function () {
                reject(media[transmorpherIdentifier].translations['validation_error']);
            }
        })
    }

    window.getVideoDimensions = function (file, transmorpherIdentifier) {
        return new Promise((resolve, reject) => {
            let video = document.createElement('video');
            video.src = URL.createObjectURL(file);

            video.onloadedmetadata = function () {
                URL.revokeObjectURL(this.src);
                resolve({
                    width: this.videoWidth,
                    height: this.videoHeight
                })
            }

            video.onerror = function () {
                reject(media[transmorpherIdentifier].translations['validation_error']);
            }
        })
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
            fetch(`${media[transmorpherIdentifier].routes.handleUploadResponse}/${uploadToken}`, {
                method: 'POST', headers: {
                    'Content-Type': 'application/json', 'X-XSRF-TOKEN': getCsrfToken(),
                }, body: JSON.stringify({
                    // When the token retrieval failed, "file" doesn't contain the http code.
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

        // Remove the uploaded file to reset the state.
        let dropzone = document.querySelector(`#dz-${transmorpherIdentifier}`).dropzone;
        dropzone.removeFile(dropzone.files[0]);
    }

    window.displayUploadResult = function (uploadResult, transmorpherIdentifier, uploadToken) {
        resetAgeElement(transmorpherIdentifier);
        // Check for undefined, which happens in cases where dropzone directly rejects the file e.g., due to max file size.
        if (uploadResult.state !== undefined && uploadResult.state !== 'error') {
            document.querySelector(`#dz-${transmorpherIdentifier}`).classList.remove('dz-started');
            document.querySelector(`#modal-mi-${transmorpherIdentifier} .card-side .confirm-delete`).classList.remove('d-hidden');
            updateVersionInformation(transmorpherIdentifier);

            switch (media[transmorpherIdentifier].mediaType) {
                case mediaTypes[IMAGE]:
                case mediaTypes[PDF]:
                    updateThumbnail(transmorpherIdentifier, uploadResult.thumbnailUrl, uploadResult.fullsizeUrl);
                    break;
                case mediaTypes[VIDEO]:
                    startPolling(transmorpherIdentifier, uploadToken)
                    break;
            }

            displayState(transmorpherIdentifier, uploadResult.state);
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

        // Don't update when the modal is closed or currently fetching.
        if (!modal.classList.contains('d-flex') || modal.dataset.fetching === 'true') {
            return;
        }

        modal.dataset.fetching = 'true';

        let versionList = modal.querySelector('.version-list > ul');
        let defaultVersionEntry = versionList.querySelector('.version-entry').cloneNode(true);

        // Clear the list of versions.
        versionList.replaceChildren();

        // We will always need an entry to be able to clone it, even when everything is deleted.
        versionList.append(defaultVersionEntry);

        // Get all versions for this media.
        fetch(media[transmorpherIdentifier].routes.getVersions, {
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

            let versions = versionInformation.state === 'success' ? versionInformation.versions : [];

            let versionAge;
            switch (media[transmorpherIdentifier].mediaType) {
                case mediaTypes[IMAGE]:
                case mediaTypes[PDF]:
                    versionAge = getDateForDisplay(new Date((versions[versionInformation.currentVersion]) * 1000));
                    updateThumbnail(transmorpherIdentifier, versionInformation.thumbnailUrl, versionInformation.fullsizeUrl)
                    break;
                case mediaTypes[VIDEO]:
                    versionAge = getDateForDisplay(new Date((versions[versionInformation.currentlyProcessedVersion]) * 1000));
                    if (versionInformation.currentlyProcessedVersion) {
                        updateVideoDisplay(transmorpherIdentifier, versionInformation.thumbnailUrl, versionInformation.fullsizeUrl);
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

                switch (media[transmorpherIdentifier].mediaType) {
                    case mediaTypes[IMAGE]:
                    case mediaTypes[PDF]:
                        versionEntry.querySelector('a').href = `${media[transmorpherIdentifier].routes.getDerivativeForVersion}/${version}`;
                        versionEntry.querySelector('.dz-image img:first-of-type').src = `${media[transmorpherIdentifier].routes.getDerivativeForVersion}/${version}/w-150`;
                        versionEntry.querySelector('.dz-image img:first-of-type').srcset = `${media[transmorpherIdentifier].routes.getDerivativeForVersion}/${version}/w-150 150w`;
                        break;
                    case mediaTypes[VIDEO]:
                        // Don't show video for now, will use thumbnails later.
                        versionEntry.querySelector('.media-preview').remove();
                }

                addConfirmEventListener(versionEntry.querySelector('button'), createCallbackWithArguments(setVersion, transmorpherIdentifier, version), transmorpherIdentifier);
                versionAge.textContent = getDateForDisplay(new Date(versions[version] * 1000));

                versionList.append(versionEntry);
                versionEntry.classList.remove('d-none');
            })
        }).finally(() => modal.dataset.fetching = 'false');
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
        fetch(media[transmorpherIdentifier].routes.setVersion, {
            method: 'POST', headers: {
                'Content-Type': 'application/json', 'X-XSRF-TOKEN': getCsrfToken()
            }, body: JSON.stringify({
                version: version,
            })
        }).then(response => {
            return response.json();
        }).then(setVersionResult => {
            if (setVersionResult.state !== 'error') {
                clearInterval(window[`statusPolling${transmorpherIdentifier}`]);
                updateVersionInformation(transmorpherIdentifier);

                switch (media[transmorpherIdentifier].mediaType) {
                    case mediaTypes[IMAGE]:
                    case mediaTypes[PDF]:
                        updateMediaDisplay(
                            transmorpherIdentifier,
                            setVersionResult.thumbnailUrl,
                            setVersionResult.fullsizeUrl
                        );
                        break;
                    case mediaTypes[VIDEO]:
                        startPolling(transmorpherIdentifier, setVersionResult.upload_token);
                        break;
                }

                displayState(transmorpherIdentifier, setVersionResult.state);
            } else {
                clearInterval(window[`statusPolling${transmorpherIdentifier}`]);
                displayModalState(transmorpherIdentifier, setVersionResult.state, setVersionResult.clientMessage);
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
        fetch(media[transmorpherIdentifier].routes.delete, {
            method: 'POST', headers: {
                'Content-Type': 'application/json', 'X-XSRF-TOKEN': getCsrfToken()
            },
        }).then(response => {
            return response.json();
        }).then(deleteResult => {
            if (deleteResult.state !== 'error') {
                clearInterval(window[`statusPolling${transmorpherIdentifier}`]);
                displayModalState(transmorpherIdentifier, 'success')
                displayCardBorderState(transmorpherIdentifier, 'processing')
                updateVersionInformation(transmorpherIdentifier);
                displayPlaceholder(transmorpherIdentifier);

                document.querySelector(`#dz-${transmorpherIdentifier}`).closest('.card').querySelector('.badge').classList.add('d-hidden');
                document.querySelector(`#modal-mi-${transmorpherIdentifier} .card-side .confirm-delete`).classList.add('d-hidden');
            } else {
                clearInterval(window[`statusPolling${transmorpherIdentifier}`]);
                displayModalState(transmorpherIdentifier, deleteResult.state, deleteResult.clientMessage);
            }
        })
    }

    window.updateMediaDisplay = function (transmorpherIdentifier, thumbnailUrl, fullsizeUrl) {
        switch (media[transmorpherIdentifier].mediaType) {
            case mediaTypes[IMAGE]:
            case mediaTypes[PDF]:
                updateThumbnail(transmorpherIdentifier, thumbnailUrl, fullsizeUrl);
                break;
            case mediaTypes[VIDEO]:
                updateVideoDisplay(transmorpherIdentifier, thumbnailUrl, fullsizeUrl);
                break;
        }
    }

    window.updateThumbnail = function (transmorpherIdentifier, thumbnailUrl, fullSizeUrl) {
        let imgElements = document.querySelectorAll(`#component-${transmorpherIdentifier} .dz-image > img:first-of-type`)

        imgElements.forEach(image => {
            image.src = thumbnailUrl;
            image.srcset = getSrcSetString(transmorpherIdentifier, thumbnailUrl);
            let aTag = image.closest('.full-size-link')
            aTag.href = fullSizeUrl;
            aTag.classList.remove('disabled');

            // Show enlarge icon.
            image.nextElementSibling.classList.remove('d-hidden');
        });
    }

    window.getSrcSetString = function (transmorpherIdentifier, imageUrl) {
        let srcStrings = []

        Object.keys(transformations).forEach(key => {
            let modifiedUrl = imageUrl.replace(/(\/).-.+(\?)/i, `$1${transformations[key]}$2`);
            srcStrings.push(`${modifiedUrl} ${key}`);
        })

        return srcStrings.join(', ');
    }

    window.updateVideoDisplay = function (transmorpherIdentifier, thumbnailUrl, fullsizeUrl) {
        let videoElements = document.querySelectorAll(`#component-${transmorpherIdentifier} video.video-transmorpher`);

        videoElements.forEach(video => {
            video.src = thumbnailUrl;
            video.querySelector('a').href = thumbnailUrl;
            video.classList.remove('d-none');
        })

        // Hide placeholder images.
        document.querySelectorAll(`#component-${transmorpherIdentifier} img.video-transmorpher`)
            .forEach(placeholder => placeholder.classList.add('d-none'));
    }

    window.displayPlaceholder = function (transmorpherIdentifier) {
        let imgElements;

        switch (media[transmorpherIdentifier].mediaType) {
            case mediaTypes[IMAGE]:
            case mediaTypes[PDF]:
                imgElements = document.querySelectorAll(`#component-${transmorpherIdentifier} .dz-image > img:first-of-type`)
                imgElements.forEach(image => {
                    let aTag = image.closest('.full-size-link')
                    aTag.href = image.dataset.placeholderUrl;
                    aTag.classList.add('disabled');

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

    window.displayState = function (transmorpherIdentifier, state, message = null, resetError = true) {
        displayDropzoneState(transmorpherIdentifier, state, message, resetError);
        displayModalState(transmorpherIdentifier, state, message, resetError);
    }

    window.displayDropzoneState = function (transmorpherIdentifier, state, message = null, resetError = true) {
        let stateInfo = document.querySelector(`#dz-${transmorpherIdentifier}`).closest('.card').querySelector('.badge');

        displayCardBorderState(transmorpherIdentifier, state)
        displayStateInformation(stateInfo, state, transmorpherIdentifier);

        if (message) {
            displayDropzoneErrorMessage(transmorpherIdentifier, message);
        } else if (resetError) {
            resetModalErrorMessageDisplay(transmorpherIdentifier, message);
        }
    }

    window.displayModalState = function (transmorpherIdentifier, state, message = null, resetError = true) {
        displayStateInformation(document.querySelector(`#modal-mi-${transmorpherIdentifier} .card-side .badge`), state, transmorpherIdentifier);

        if (message) {
            setModalErrorMessage(transmorpherIdentifier, message);
        } else if (resetError) {
            resetModalErrorMessageDisplay(transmorpherIdentifier, message);
        }
    }

    window.displayStateInformation = function (stateInfoElement, state, transmorpherIdentifier) {
        stateInfoElement.className = '';
        stateInfoElement.classList.add('badge', `badge-${state}`);
        stateInfoElement.querySelector('span:first-of-type').textContent = media[transmorpherIdentifier].translations[state];
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
            previewElement ? previewElement.style.display = 'block' : null;
            document.querySelector(`#modal-uc-${transmorpherIdentifier}`).classList.remove('d-flex');

            // If there is an upload in progress, remove it.
            if (dropzone.files[0]?.status === 'uploading') {
                // If a version was restored, show the default message.
                if (!dropzone.files[1]) {
                    document.querySelector(`#dz-${transmorpherIdentifier} .dz-default`).style.display = 'block';
                }

                dropzone.removeFile(dropzone.files[0]);
            } else if (dropzone.files[0]) {
                // If the file is not uploading, the overwrite button was clicked after finishing the upload. Display the progressbar.
                document.querySelector(`#dz-${transmorpherIdentifier} .dz-default`).style.display = 'none';
                previewElement ? previewElement.style.display = 'block' : null;
            }

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
        fetch(media[transmorpherIdentifier].routes.uploadToken, {
            method: 'POST', headers: {
                'Content-Type': 'application/json', 'X-XSRF-TOKEN': getCsrfToken()
            }, body: JSON.stringify({
                transmorpher_media_key: media[transmorpherIdentifier].transmorpherMediaKey,
            }),
        }).then(response => {
            return response.json();
        }).then(getUploadTokenResult => {
            if (getUploadTokenResult.state === 'error') {
                done(getUploadTokenResult);
            }

            let dropzone = document.querySelector(`#dz-${transmorpherIdentifier}`).dropzone;
            dropzone.options.uploadToken = getUploadTokenResult.upload_token
            // Set the dropzone target to the media server upload url, which needs a valid upload token.
            dropzone.options.url = `${media[transmorpherIdentifier].webUploadUrl}${getUploadTokenResult.upload_token}`;

            done()
        });
    }

    window.getState = function (transmorpherIdentifier, uploadToken = null) {
        return fetch(media[transmorpherIdentifier].routes.state, {
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

    window.addConfirmEventListener = function (button, callback, transmorpherIdentifier) {
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
                button.querySelector('span').textContent = media[transmorpherIdentifier].translations['press_again_to_confirm'];
                pressedOnce = true;
                timeOut = setTimeout(() => {
                    pressedOnce = false;
                    button.querySelector('span').textContent = buttonText;
                }, 3000);
            }
        });
    }
}
