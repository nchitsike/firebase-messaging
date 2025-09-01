<?php

namespace FirebaseMessaging;

use FirebaseMessaging\Exceptions\FirebaseMessagingException;

class FirebaseMessaging
{
    /**
     * Obtains an access token for Firebase Cloud Messaging.
     *
     * @param string $serviceAccountFile Path to the service account JSON file
     * @return string Access token
     * @throws FirebaseMessagingException
     */
    public function getAccessToken(string $serviceAccountFile): string
    {
        $audience = "https://oauth2.googleapis.com/token";

        // Validate service account file
        if (!file_exists($serviceAccountFile)) {
            throw new FirebaseMessagingException("Service account file not found: $serviceAccountFile");
        }

        $serviceAccount = json_decode(file_get_contents($serviceAccountFile), true);
        if (!$serviceAccount) {
            throw new FirebaseMessagingException("Failed to parse service account JSON");
        }

        $clientEmail = $serviceAccount['client_email'] ?? null;
        $privateKey = str_replace("\\n", "\n", $serviceAccount['private_key'] ?? '');

        if (!$clientEmail || !$privateKey) {
            throw new FirebaseMessagingException("Missing client_email or private_key in service account file");
        }

        // JWT Header
        $header = json_encode(['alg' => 'RS256', 'typ' => 'JWT']);
        $header = $this->rfc2045Base64Encode($header);

        // JWT Claim Set
        $now = time();
        $claimSet = json_encode([
            'iss' => $clientEmail,
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud' => $audience,
            'iat' => $now,
            'exp' => $now + 3600,
        ]);
        $claimSet = $this->rfc2045Base64Encode($claimSet);

        // Signature
        $data = "$header.$claimSet";
        $signature = '';
        if (!openssl_sign($data, $signature, $privateKey, 'SHA256')) {
            throw new FirebaseMessagingException("Failed to sign JWT: " . openssl_error_string());
        }
        $signature = $this->rfc2045Base64Encode($signature);

        // JWT
        $jwt = "$data.$signature";

        // Request access token
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://oauth2.googleapis.com/token");
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt,
        ]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded'
        ]);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            throw new FirebaseMessagingException("cURL error: " . curl_error($ch));
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $responseJson = json_decode($response, true);

        if ($httpCode !== 200 || !$responseJson) {
            throw new FirebaseMessagingException("Token request failed: " . ($responseJson['error_description'] ?? $response));
        }

        if (!isset($responseJson['access_token'])) {
            throw new FirebaseMessagingException("No access token in response: " . json_encode($responseJson));
        }

        return $responseJson['access_token'];
    }

    /**
     * Sends a notification using Firebase Cloud Messaging.
     *
     * @param string $accessToken Access token from getAccessToken
     * @param array $notificationData Notification data including token and data payload
     * @param string $projectId Firebase project ID
     * @return array Response from FCM
     * @throws FirebaseMessagingException
     */
    public function sendNotification(string $accessToken, array $notificationData, string $projectId): array
    {
        $url = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";
        $headers = [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json',
        ];

        $payload = json_encode([
            'message' => [
                'token' => $notificationData['token'] ?? null,
                'data' => $notificationData['data'] ?? [],
                'android' => [
                    'priority' => 'HIGH',
                ],
                'apns' => [
                    'headers' => [
                        'apns-priority' => '10',
                    ],
                    'payload' => [
                        'aps' => [
                            'alert' => [
                                'title' => $notificationData['data']['title'] ?? '',
                                'body' => $notificationData['data']['body'] ?? '',
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            throw new FirebaseMessagingException("cURL error: " . curl_error($ch));
        }

        curl_close($ch);

        $responseJson = json_decode($response, true);
        if (!$responseJson) {
            throw new FirebaseMessagingException("Invalid response from FCM: " . $response);
        }

        return $responseJson;
    }

    /**
     * Custom base64 encoding for RFC 2045 compliance.
     *
     * @param string $data Data to encode
     * @return string Encoded string
     */
    private function rfc2045Base64Encode(string $data): string
    {
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($data));
    }
}
?>