import {closeUploadConfirmModal, openMoreInformationModal, setupComponent} from './controller.js';
import {closeErrorMessage, closeMoreInformationModal} from './ui.js';
import {registerMedium, setUploadHandler} from './state.js';
import {registerRoutes} from "./routes";

if (!window.transmorpherScriptLoaded) {
    window.transmorpherScriptLoaded = true;
    window.transmorpher = {
        setUploadHandler,
        registerMedium,
        registerRoutes,
        setupComponent,
        openMoreInformationModal,
        closeMoreInformationModal,
        closeUploadConfirmModal,
        closeErrorMessage,
    };
}
