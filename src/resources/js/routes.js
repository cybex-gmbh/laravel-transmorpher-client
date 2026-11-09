export let routes = {
    media: {
        delete: '',
        getState: '',
        reserveUpload: '',
    },
    uploads: {
        abort: '',
        complete: '',
        handleResponse: '',
        setState: '',
        url: '',
    },
    versions: {
        get: '',
        getDerivative: '',
        getOriginal: '',
        set: '',
    },
}

export function registerRoutes({routes: newRoutes}) {
    routes = newRoutes;
}