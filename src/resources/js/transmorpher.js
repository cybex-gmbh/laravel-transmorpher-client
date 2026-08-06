import {closeErrorMessage, closeMoreInformationModal, closeUploadConfirmModal, openMoreInformationModal, setupComponent,} from './controller.js';
import {registerMediaTypes, registerMedium, setUploadHandler} from './state.js';

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
