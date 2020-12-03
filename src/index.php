<?php

require_once __DIR__ . "/../vendor/autoload.php";

use TelegramBot\Api\BotApi;
use TelegramBot\Api\Types\Message;
use TelegramBot\Api\Types\Update;
use TelegramBot\Api\Client;
use SpotifyWebAPI\Session;
use SpotifyWebAPI\SpotifyWebAPI;

/* Tokens. */
$telegramToken = getenv('TELEGRAM_TOKEN');
$spotifyClientId = getenv('SPOTIFY_CLIENT_ID');
$spotifyClientSecret = getenv('SPOTIFY_CLIENT_SECRET');

/* Initialize clients */
$bot = new botApi($telegramToken);
$client = new Client($telegramToken);
$spotify = new Session($spotifyClientId, $spotifyClientSecret);
$spotify->requestCredentialsToken();
$spotifyApi = new SpotifyWebAPI();
$spotifyApi->setAccessToken($spotify->getAccessToken());

try {
    /* /top %country% command */
    $client->command('top', function (Message $message) use ($bot, $spotifyApi) {
        $playlist = $spotifyApi->search(substr($message->getText(), 5) . ' top 50 chart', 'playlist', ['limit' => 1]);
        $playlistId = $playlist->playlists->items[0]->id;
        $songs = $spotifyApi->getPlaylistTracks($playlistId);

        $result = sprintf("<b>%s:</b>\n", $playlist->playlists->items[0]->name);
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

    /* /recommend %song name% command */
    $client->command('recommend', function (Message $message) use ($bot, $spotifyApi) {
        $track = $spotifyApi->search(substr($message->getText(), 11), 'track', ['limit' => 1]);
        $artists = array_map(fn($artist) => $artist->name, $track->tracks->items[0]->artists);
        $artists = join(', ', $artists);
        $result = sprintf("Recommendations for <b>«%s – %s»</b>:\n", $artists, $track->tracks->items[0]->name);

        if ($trackId = $track->tracks->items[0]->id) {
            $artistId = $track->tracks->items[0]->artists[0]->id;
            $recommends = $spotifyApi->getRecommendations([
                'limit' => 5,
                'seed_artists' => [$artistId],
                'seed_tracks' => [$trackId],
            ]);

            foreach ($recommends->tracks as $track) {
                $artists = array_map(fn($artist) => $artist->name, $track->artists);
                $artists = join(', ', $artists);
                $temp = sprintf("<a href=\"%s\">• %s – %s</a>\n", $track->external_urls->spotify, $artists, $track->name);
                $result .= $temp;
            }
        } else {
            $result .= 'Sorry, nothing was found';
        }

        $bot->sendMessage(
            $message->getChat()->getId(),
            $result,
            'HTML',
            true
        );
    });

    /* /newreleases | optional country code command */
    $client->command('newreleases', function (Message $message) use ($bot, $spotifyApi) {
       if (strlen($message->getText()) > 12) {
           $countryCode = substr($message->getText(), 13);
       } else {
           $countryCode = 'US';
       }
       $releases = $spotifyApi->getNewReleases(['country' => $countryCode, 'limit' => 10]);
       $result = "<b>Latest releases:</b>\n";

       foreach ($releases->albums->items as $release) {
           $artists = array_map(fn($artist) => $artist->name, $release->artists);
           $artists = join(', ', $artists);
           $temp = sprintf("<a href=\"%s\">• %s – %s</a>\n", $release->external_urls->spotify, $artists, $release->name);
           $result .= $temp;
       }

       $bot->sendMessage(
           $message->getChat()->getId(),
           $result,
           'HTML',
           true
       );
    });

    /* /randomsong | optional genre command */
    $client->command('randomsong', function(Message $message) use ($bot, $spotifyApi) {
        if (strlen($message->getText()) > 11) {
            $genre = substr($message->getText(), 12);
        } else {
            $genres = $spotifyApi->getGenreSeeds()->genres;
            $genre = $genres[array_rand($genres, 1)];
        }

        $song = $spotifyApi->getRecommendations([
            'limit' => 1,
            'seed_genres' => [$genre],
            'min_popularity' => 50,
        ]);
        $songLink = $song->tracks[0]->external_urls->spotify;

        $bot->sendMessage(
            $message->getChat()->getId(),
            $songLink
        );
    });

    /* /randomalbum | optional genre command */
    $client->command('randomalbum', function (Message $message) use ($bot, $spotifyApi) {
        if (strlen($message->getText()) > 12) {
            $genre = substr($message->getText(), 13);
        } else {
            $genres = $spotifyApi->getGenreSeeds()->genres;
            $genre = $genres[array_rand($genres, 1)];
        }

       $album = $spotifyApi->getRecommendations([
           'limit' => 1,
           'seed_genres' => [$genre],
           'min_popularity' => 50,
       ]);
       $albumLink = $album->tracks[0]->album->external_urls->spotify;

        $bot->sendMessage(
            $message->getChat()->getId(),
            $albumLink
        );
    });

    /* /genres command */
    $client->command('genres', function (Message $message) use ($bot, $spotifyApi) {
       $genres = $spotifyApi->getGenreSeeds()->genres;

       $bot->sendMessage(
           $message->getChat()->getId(),
           join(', ', $genres)
       );
    });

    /* /start command */
    $client->command('start', function (Message $message) use ($bot) {
        $bot->sendMessage(
            $message->getChat()->getId(),
            'Welcome to Spotify Music bot!'
        );
    });

    /* Any messages other than commands */
    $client->on(function (Update $update) use ($bot) {
        $message = $update->getMessage();
        $id = $message->getChat()->getId();
        $bot->sendMessage($id, 'Please try to use commands, for example /randomsong');
    }, function () {
        return true;
    });

    /* Send response to bot */
    $client->run();
} catch (\Throwable $e) {
    $client->on(function (Update $update) use ($bot) {
        $bot->sendMessage($update->getMessage()->getChat()->getId(), 'Sorry, something went wrong');
    });
}
