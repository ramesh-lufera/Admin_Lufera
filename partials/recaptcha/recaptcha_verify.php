<?php

require_once __DIR__ . '/recaptcha.php';

function verifyRecaptcha($token, $action)
{
    if (empty($token)) {
        return [
            'success' => false,
            'message' => 'Missing reCAPTCHA token.'
        ];
    }

    $url = "https://www.google.com/recaptcha/api/siteverify";

    $postData = [
        'secret'   => RECAPTCHA_SECRET_KEY,
        'response' => $token
    ];

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {

        return [
            'success' => false,
            'message' => curl_error($ch)
        ];

    }

    curl_close($ch);

    $result = json_decode($response, true);

    if (!$result['success']) {

        return [
            'success' => false,
            'message' => 'Google verification failed.'
        ];

    }

    if ($result['score'] < RECAPTCHA_MIN_SCORE) {

        return [
            'success' => false,
            'message' => 'Low reCAPTCHA score.'
        ];

    }

    if ($result['action'] != $action) {

        return [
            'success' => false,
            'message' => 'Action mismatch.'
        ];

    }

    if (isset($result['hostname'])) {

        if ($result['hostname'] != $_SERVER['SERVER_NAME']) {

            return [
                'success' => false,
                'message' => 'Hostname mismatch.'
            ];

        }

    }

    return [
        'success'        => true,
        'score'          => $result['score'],
        'google_response'=> $result
    ];

}