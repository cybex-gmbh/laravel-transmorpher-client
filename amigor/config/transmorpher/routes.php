<?php

return [
    // The middleware applied to routes provided by this package:
    // - the "SubstituteBindings" middleware will be applied additionally.
    // - "web" and "auth" middleware will be applied when this is not set.
    'middleware' => [],

    // The URL prefix applied to routes provided by this package.
    'prefix' => 'transmorpher',

    // The route the Transmorpher server can use to send signed notifications to.
    // Will be prefixed with the prefix config value, full default route: "transmorpher/notifications"
    'notifications' => 'notifications',
];

