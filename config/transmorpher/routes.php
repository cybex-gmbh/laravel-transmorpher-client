<?php

return [
    // The middleware applied to routes provided by this package:
    // - the "SubstituteBindings" middleware will be applied additionally.
    // - "web" and "auth" middleware will be applied when this is not set.
//    'middleware' => ['web', 'auth'],

    // The URL prefix applied to routes provided by this package.
    'prefix' => 'transmorpher',

    // The route the Transmorpher server can use to send signed notifications to.
    // Will be prefixed with "transmorpher", full default route: "transmorpher/notifications"
    'notifications' => 'notifications',
];

