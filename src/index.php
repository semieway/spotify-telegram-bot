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

$client->command('top', function (Message $message) use ($bot, $spotifyApi) {
    $playlist = $spotifyApi->search($message->getText() . ' top 50 chart', 'playlist', ['limit' => 1]);
    $playlistId = $playlist->playlists->items[0]->id;
    $songs = $spotifyApi->getPlaylistTracks($playlistId);

    $result = sprintf("<b>%s:</b>\n\n", $playlist->playlists->items[0]->name);
    foreach ($songs->items as $key => $item) {
        $artists = array_map(fn($artist) => $artist->name, $item->track->artists);
        $artists = join(', ', $artists);
        $temp = sprintf("<a href=\"%s\"><b>%d.</b> %s – %s</a>\n", $item->track->external_urls->spotify, $key + 1, $artists, $item->track->name);
        $result .= $temp;
    }

    $bot->sendMessage(
        $message->getChat()->getId(),
        $result,
        'HTML',
        true
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

try {
    $client->run();
} catch (\TelegramBot\Api\InvalidJsonException $e) {
    $client->on(function (Update $update) use ($bot) {
       $bot->sendMessage($update->getMessage()->getChat()->getId(), 'Sorry, this bot currently unavailable');
    });
}
