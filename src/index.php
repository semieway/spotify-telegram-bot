<?php

require_once __DIR__ . "/../vendor/autoload.php";

use TelegramBot\Api\BotApi;
use TelegramBot\Api\Types\Message;
use TelegramBot\Api\Types\Update;
use TelegramBot\Api\Client;

$telegramToken = getenv('TELEGRAM_TOKEN');
$spotifyToken = getenv('SPOTIFY_TOKEN');

$bot = new botApi($telegramToken);
$client = new Client($telegramToken);

$client->command('getrandomtopsong', function (Message $message) use ($bot) {
    $bot->sendMessage(
        $message->getChat()->getId(),
        'Some text'
    );
});

$client->command('start', function (Message $message) use ($bot) {
   $bot->sendMessage(
       $message->getChat()->getId(),
       'Welcome to Spotify Music bot! Use /getrandomtopsong, for example'
   );
});

$client->on(function (Update $update) use ($bot) {
    $message = $update->getMessage();
    $id = $message->getChat()->getId();
    $bot->sendMessage($id, 'Please try to use commands, for example /getrandomtopsong');
}, function () {
    return true;
});

$client->run();
