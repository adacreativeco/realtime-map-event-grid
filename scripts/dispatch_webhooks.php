<?php

require_once __DIR__ . '/../src/Webhook.php';

Webhook::dispatchPending();

echo "Dispatch complete\n";

