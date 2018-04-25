<?php

require_once(__DIR__ . '/vendor/autoload.php');

/*
 * Let's define who we will notify
 * And who we will listen to for text messages
 *    Montreal 2018 group
 */
$authorizedUsers = [
    ['name' => 'Person 1',  'mobile' => '+16134567890'], 
    ['name' => 'PErson 2',  'mobile' => '+16134568901'], 
];

/*
 * Twilio settings for authorization
 */
$twilioSettings = [
    'accountSid' => ' ** Twilio Account SID ** ',
    'authToken'  => ' ** Twilio Account Auth Token ** ',
    'myNumber'   => ' ** Twilio phone number  eg. +12324445656 ** '
];

// Let's avoid any issues with an unset message...
if (isset($_REQUEST['body']) && !empty($_REQUEST['body'])) {
    $_REQUEST['Body'] = $_REQUEST['body'];
}
$receivedMessage = isset($_REQUEST['Body']) ? $_REQUEST['Body'] : '';


$picCount = 0;
$media = "";
$mediaUrl = $_REQUEST['MediaUrl' . $picCount ];
while( $mediaUrl != "" ) {
    $media .= shortenURL_firebase($mediaUrl);
    $picCount++;
    $mediaUrl = $_REQUEST['MediaUrl' . $picCount ];
}

// If we don't have a message to work with, let's stop here.
if (empty($receivedMessage) && empty($media)) {
    $response = new Services_Twilio_Twiml;
    print $response;
    exit();
}

// We have a message to work with now -- Let's proceed
$twilio = new Services_twilio($twilioSettings['accountSid'], $twilioSettings['authToken']);

// Twilio message
if (isset($_REQUEST['MessageSid']) && isset($_REQUEST['From'])) {
    
    $authorized = 0;
    $receivedFrom = [];
    foreach ($authorizedUsers as $user) {
        if ($user['mobile'] == $_REQUEST['From']) {
            $authorized = 1;
            $receivedFrom = $user;
        }
    }
    if ($authorized == 1) {
        $response = new Services_Twilio_Twiml;

        $message = $receivedMessage . $media . '  -' . $receivedFrom['name'];

        foreach ($authorizedUsers as $user) {
            $response->message($message, [
                'to' => $user['mobile'],
                'from' => $twilioSettings['myNumber']
            ]);
        }
        print $response;

    } else {
        $response = new Services_Twilio_Twiml;
        print $response;
    }
    exit();
}

// Firebase shorten link
function shortenURL_firebase($longUrl) {
     $firebaseKey = "  ** Firebase auth key  ** ";
     $completeURL = "https://". " ** Firebase short project URL ** "  ."/?link=" . $longUrl;

     $jsonData = "{
        'longDynamicLink' : '" . $completeURL . "',
        'suffix' : {'option':'SHORT'}
    }";

    $curlObj = curl_init();
    curl_setopt($curlObj, CURLOPT_URL, "https://firebasedynamiclinks.googleapis.com/v1/shortLinks?key=".$firebaseKey);
    curl_setopt($curlObj, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curlObj, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($curlObj, CURLOPT_HEADER, 0);
    curl_setopt($curlObj, CURLOPT_HTTPHEADER, array('Content-type:application/json'));
    curl_setopt($curlObj, CURLOPT_POST, 1);
    curl_setopt($curlObj, CURLOPT_POSTFIELDS, $jsonData);

    $response = curl_exec($curlObj);

    $json = json_decode($response);
    curl_close($curlObj);

    return ("\r\n" . $json->shortLink);
}



