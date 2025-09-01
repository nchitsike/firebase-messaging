<?php

require 'vendor/autoload.php';

use FirebaseMessaging\FirebaseMessaging;
use FirebaseMessaging\Exceptions\FirebaseMessagingException;

try {
    $firebase = new FirebaseMessaging();
    $serviceAccountFile = '/path-to-your-services-file.json';
    $projectId = 'YOUR-PROJECT-ID';

    $accessToken = $firebase->getAccessToken($serviceAccountFile);

    $notificationData = [
        'token' => 'YOUR-DEVICE_TOKEN',
        'data' => [
            'title' => 'NOTIFICATION TITLE',
            'body' => 'NOTIFICATION BODY',
            'expiry' => date(DATE_ISO8601),
            'id' => 'UNIQUE ID',
        ],
    ];

    $response = $firebase->sendNotification($accessToken, $notificationData, $projectId);

    if (isset($response['name'])) {
        echo 'Notification sent successfully!';
    } else {
        echo 'Error sending notification: ' . json_encode($response);
    }
} catch (FirebaseMessagingException $e) {
    echo 'Error: ' . $e->getMessage();
}
