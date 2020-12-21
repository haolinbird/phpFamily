<?php

require_once (__DIR__ . '/../Tracker.php');

var_dump(\JmArchiTracker\Tracker::isBenchUid(10000));
var_dump(\JmArchiTracker\Tracker::isBenchUid(500000000));
var_dump(\JmArchiTracker\Tracker::isBenchUid(500000001));
var_dump(\JmArchiTracker\Tracker::isBenchUid(500000004));
var_dump(\JmArchiTracker\Tracker::isBenchUid(500000009));
var_dump(\JmArchiTracker\Tracker::isBenchUid(500000010));


