import {closeUploadConfirmModal, openMoreInformationModal, setupComponent,} from './controller.js';
import {closeErrorMessage, closeMoreInformationModal} from './ui.js';
import {registerMedium, setUploadHandler} from './state.js';

if (!window.transmorpherScriptLoaded) {
    window.transmorpherScriptLoaded = true;
    window.transmorpher = {
        setUploadHandler,
        registerMedium,
        setupComponent,
        openMoreInformationModal,
        closeMoreInformationModal,
        closeUploadConfirmModal,
        closeErrorMessage,
    };
}
