<?php

require_once __DIR__ . "/../vendor/autoload.php";

use TelegramBot\Api\BotApi;
use TelegramBot\Api\Types\Message;
use TelegramBot\Api\Types\ReplyKeyboardMarkup;
use TelegramBot\Api\Types\Update;

$telegramToken = getenv('TELEGRAM_TOKEN');
$spotifyToken = getenv('SPOTIFY_TOKEN');

$bot = new botApi($telegramToken);

$bot->command('start', function (Message $message, $code) use ($bot) {
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

$bot->on(function (Update $update) use ($bot) {
   $message = $update->getMessage();
   $id = $message->getChat()->getId();
   $bot->sendMessage($id, 'Your message: ' . $message->getText());
}, function () {
    return true;
});

$bot->run();