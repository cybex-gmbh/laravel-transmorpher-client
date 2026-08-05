import {
    closeErrorMessage,
    closeMoreInformationModal,
    closeUploadConfirmModal,
    openMoreInformationModal,
    registerMediaTypes,
    registerMedium,
    setupComponent,
    setUploadHandler,
} from './controller.js';

if (!window.transmorpherScriptLoaded) {
    window.transmorpherScriptLoaded = true;
    window.transmorpher = {
        registerMediaTypes,
        setUploadHandler,
        registerMedium,
        setupComponent,
        openMoreInformationModal,
        closeMoreInformationModal,
        closeUploadConfirmModal,
        closeErrorMessage,
    };
}
