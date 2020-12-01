<?php

require_once __DIR__ . "/../vendor/autoload.php";

use TelegramBot\Api\BotApi;
use TelegramBot\Api\Types\Message;
use TelegramBot\Api\Types\Update;
use TelegramBot\Api\Client;
use SpotifyWebAPI\Session;
use SpotifyWebAPI\SpotifyWebAPI;

$telegramToken = getenv('TELEGRAM_TOKEN');
$spotifyClientId = getenv('SPOTIFY_CLIENT_ID');
$spotifyClientSecret = getenv('SPOTIFY_CLIENT_SECRET');

$bot = new botApi($telegramToken);
$client = new Client($telegramToken);
$spotify = new Session($spotifyClientId, $spotifyClientSecret);
$spotify->requestCredentialsToken();
$spotifyApi = new SpotifyWebAPI();
$spotifyApi->setAccessToken($spotify->getAccessToken());

$client->command('getrandomtopsong', function (Message $message) use ($bot, $spotifyApi) {
    $playlist = $spotifyApi->getPlaylistTracks('37i9dQZEVXbMDoHDwVN2tF');
    file_put_contents("php://stderr", serialize($playlist));

    $bot->sendMessage(
        $message->getChat()->getId(),
        'Test'
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
