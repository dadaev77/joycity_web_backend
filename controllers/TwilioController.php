<?php

namespace app\controllers;

use Twilio\Rest\Client;
use yii\web\Controller;

class TwilioController extends Controller
{
    public function actionIndex()
    {
        $accountSid = 'ACe35d499985a02945a87657e9aa31c9e2'; // Your Twilio account sid
        $authToken = 'f96fc636a51c8b00baae900674b937a7'; // Your Twilio auth token

        $client = new Client($accountSid, $authToken);

        return $client;
    }

    public function actionListConversations()
    {
        $accountSid = 'ACe35d499985a02945a87657e9aa31c9e2'; // Your Twilio account sid
        $authToken = 'f96fc636a51c8b00baae900674b937a7'; // Your Twilio auth token

        $client = new Client($accountSid, $authToken);

        $conversations = $client->conversations->v1->conversations->read();

        foreach ($conversations as $conversation) {
            echo 'Participants: ' . $conversation->participants->page() . "\n";
            echo "Conversation SID: " . $conversation->sid . "\n";
            echo "Conversation Friendly Name: " . $conversation->friendlyName . "\n";
            echo "Conversation Date Created: " . $conversation->dateCreated->format('Y-m-d H:i:s') . "\n";
            echo "Conversation Date Updated: " . $conversation->dateUpdated->format('Y-m-d H:i:s') . "\n";
            echo "Conversation State: " . $conversation->state . "\n";
            echo "----------------------------------------\n";
        }
    }
}
