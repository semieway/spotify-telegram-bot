<?php

require_once __DIR__ . "/../vendor/autoload.php";

use TelegramBot\Api\BotApi;
use TelegramBot\Api\Types\Message;
use TelegramBot\Api\Types\ReplyKeyboardMarkup;
use TelegramBot\Api\Types\Update;
use TelegramBot\Api\Client;

$telegramToken = getenv('TELEGRAM_TOKEN');
$spotifyToken = getenv('SPOTIFY_TOKEN');

$bot = new botApi($telegramToken);
$client = new Client($telegramToken);

$client->command('start', function (Message $message, $code) use ($bot) {
   $bot->sendMessage(
       $message->getChat()->getId(),
       'Welcome to Spotify Music Bot!',
       'html',
       false,
       null,
       new ReplyKeyboardMarkup(
           [
               ['Get a song from Global Top'],
           ]
       )
   );
});

$client->on(function (Update $update) use ($bot) {
   $message = $update->getMessage();
   $id = $message->getChat()->getId();
   $bot->sendMessage($id, 'Your message: ' . $message->getText());
}, function () {
    return true;
});

$client->run();
