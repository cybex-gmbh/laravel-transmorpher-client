<?php

return [
    // The middlewares applied to routes provided by this package:
    // - the "SubstituteBindings" middleware will be applied additionally.
    // - "web" and "auth" middlewares will be applied when this is not set.
    'middleware' => [],

    // The route the Transmorpher server can use to send signed notifications to.
    // Will be prefixed with "transmorpher", full default route: "transmorpher/notifications"
    'notifications' => 'notifications',
];

