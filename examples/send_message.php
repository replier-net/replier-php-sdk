<?php
require __DIR__ . '/../vendor/autoload.php';

$client = new \Replier\Client([
    'instanceId' => getenv('REPLIER_INSTANCE_ID'),
    'token'      => getenv('REPLIER_TOKEN'),
]);

try {
    print_r($client->status());
    print_r($client->sendMessage('15551234567', 'Hello from the Replier PHP SDK!'));
} catch (\Replier\ReplierException $e) {
    echo 'Error ' . $e->getErrorCode() . ': ' . $e->getMessage() . PHP_EOL;
}
