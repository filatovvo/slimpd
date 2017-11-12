<?php
/* Copyright (C) 2015-2016 othmar52 <othmar52@users.noreply.github.com>
 *
 * This file is part of sliMpd - a php based mpd web client
 *
 * This program is free software: you can redistribute it and/or modify it
 * under the terms of the GNU Affero General Public License as published by the
 * Free Software Foundation, either version 3 of the License, or (at your
 * option) any later version.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE. See the GNU Affero General Public License
 * for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

// Routes

$ctrlRoutes = [
    'Library' => [
        ['/library[/]', 'indexAction', 'library']
    ],
     /*
    'Auth' => [
        ['/auth/signup', 'getSignUp', 'auth.signup'],
        ['/auth/signup', 'postSignUp', 'auth.signup', 'post'],
        ['/auth/signin', 'getSignIn', 'auth.signin'],
        ['/auth/signin', 'postSignIn', 'auth.signin', 'post']
    ],
    */
    'Filebrowser' => [
        ['/filebrowser[/]', 'index', 'filebrowser'],
        ['/filebrowser/{itemParams:.*}', 'dircontent'],
        ['/markup/widget-directory/[{itemParams:.*}]', 'widgetDirectory'],
        ['/deliver/[{itemParams:.*}]', 'deliverAction']
    ],
    'Bitmap' => [
        ['/imagefallback-{imagesize}/{type}','fallback', 'imagefallback'],
        ['/image-{imagesize}/album[/]', 'album'], // vanished albums
        ['/image-{imagesize}/album/{itemUid}', 'album', 'imagealbum'],
        ['/image-{imagesize}/track[/]', 'track'], // vanished tracks
        ['/image-{imagesize}/track/{itemUid}', 'track'],
        ['/image-{imagesize}/id/{itemUid}', 'bitmap'],
        ['/image-{imagesize}/path/[{itemParams:.*}]', 'path', 'imagepath'],
        ['/image-{imagesize}/searchfor/[{itemParams:.*}]', 'searchfor']
    ],
    'Album' => [
        ['/album/{itemUid}', 'detailAction'],
        ['/markup/albumtracks/{itemUid}', 'albumTracksAction'],
        ['/markup/widget-album/{itemUid}', 'widgetAlbumAction'],
        ['/albums/page/{currentPage}/sort/{sort}/{direction}', 'listAction'],
        ['/maintainance/albumdebug/[{itemParams:.*}]', 'editAction'],
        ['/maintainance/updatealbum/{itemUid}', 'updateAction', '', 'post'],
        ['/album/remigrate/{itemUid}', 'remigrateAction'],
        ['/download-album/{itemUid}', 'downloadAction']
    ],
    'Systemcheck' => [
        ['/systemcheck', 'runAction', 'systemcheck']
    ],
    'Playlist' => [
        ['/playlists', 'indexAction'],
        ['/showplaylist/[{itemParams:.*}]', 'showAction'],
        ['/markup/widget-playlist/[{itemParams:.*}]', 'widgetAction']
    ],
    'Track' => [
        ['/markup/mpdplayer', 'mpdplayerAction'],
        ['/markup/localplayer', 'localplayerAction'],
        ['/markup/widget-trackcontrol', 'widgetTrackcontrolAction'],
        #['/markup/widget-xwax', 'widgetXwaxAction'],
        ['/markup/widget-deckselector', 'widgetDeckselectorAction'],
        ['/markup/standalone-trackview', 'standaloneTrackviewAction'],
        ['/maintainance/trackid3/[{itemParams:.*}]', 'dumpid3Action'],
        ['/maintainance/trackdebug/[{itemParams:.*}]', 'editAction'],
        ['/stemplayer/[{itemParams:.*}]', 'stemplayerAction']
    ],
    'Artist' => [
        ['/artists/[{itemParams:.*}]', 'listAction'],
    ],
    'Genre' => [
        ['/genres/[{itemParams:.*}]', 'listAction'],
    ],
    'Label' => [
        ['/labels/[{itemParams:.*}]', 'listAction'],
    ],
    'Tools' => [
        ['/css/spotcolors.css', 'spotcolorsCssAction'],
        ['/css/localplayer/{relPathHash}', 'localPlayerCssAction'],
        ['/css/mpdplayer/{relPathHash}', 'mpdPlayerCssAction'],
        ['/css/xwaxplayer/{relPathHash}', 'xwaxPlayerCssAction'],
        ['/showplaintext/[{itemParams:.*}]', 'showplaintextAction'],
        ['/tools/clean-rename-confirm/[{itemParams:.*}]', 'cleanRenameConfirmAction'],
        ['/tools/clean-rename/[{itemParams:.*}]', 'cleanRenameAction'],
    ],
    'WaveformGenerator' => [
        ['/audiosvg/width/{width}/[{itemParams:.*}]', 'svgAction'],
        ['/audiojson/resolution/{width}/[{itemParams:.*}]', 'jsonAction'],
    ],
    'Mpd' => [
        ['/mpdstatus[/]', 'mpdstatusAction'],
        ['/mpdctrl/{cmd}', 'cmdAction'],
        ['/mpdctrl/{cmd}/[{item:.*}]', 'cmdAction'],
        ['/playlist/page/{pagenum}', 'playlistAction'],
    ],
    'Search' => [
        ['/{className}/{itemUid}/{show}s/page/{currentPage}/sort/{sort}/{direction}', 'listAction', 'search-list'],
        ['/search{currentType}/page/{currentPage}/sort/{sort}/{direction}', 'searchAction', 'search'],
        ['/autocomplete/{type}/', 'autocompleteAction', 'autocomplete'],
        ['/directory/[{itemParams:.*}]', 'directoryAction'],
        ['/alphasearch/', 'alphasearchAction'],
    ],
    'Importer' => [
        ['/importer[/]', 'indexAction'],
        ['/importer/triggerUpdate', 'triggerUpdateAction']
    ],
    'Xwax' => [
        ['/xwaxstatus[/]', 'statusAction'],
        ['/markup/xwaxplayer', 'xwaxplayerAction'],
        ['/markup/widget-xwax', 'widgetAction'],
        ['/xwax/load_track/{deckIndex}/[{itemParams:.*}]', 'cmdLoadTrackAction'],
        ['/xwax/reconnect/{deckIndex}', 'cmdReconnectAction'],
        ['/xwax/disconnect/{deckIndex}', 'cmdDisconnectAction'],
        ['/xwax/recue/{deckIndex}', 'cmdRecueAction'],
        ['/xwax/cycle_timecode/{deckIndex}', 'cmdCycleTimecodeAction'],
        ['/xwax/launch', 'cmdLaunchAction'],
        ['/xwax/exit', 'cmdExitAction'],
        ['/djscreen', 'djscreenAction']

    ],
    'Discogs' => [
        ['/discogs/verify', 'verifyAction']
    ],
];

foreach($ctrlRoutes as $ctrlName => $ctrlRoutes) {
    foreach($ctrlRoutes as $ctrlRoute) {
        $routeName = (isset($ctrlRoute[2]) === TRUE) ? $ctrlRoute[2] : '';
        $routeMethod = (isset($ctrlRoute[3]) === TRUE) ? $ctrlRoute[3] : 'get';
        $app->$routeMethod(
            $ctrlRoute[0],
            'Slimpd\Modules\\'. $ctrlName .'\Controller' . ':' . $ctrlRoute[1]
        )->setName($routeName);
    }
}

$app->get('/auth/signup', 'Slimpd\Controllers\Auth\AuthController:getSignUp')->add($container->get('csrf'))->setName('auth.signup');
$app->post('/auth/signup', 'Slimpd\Controllers\Auth\AuthController:postSignUp');
$app->get('/auth/signin', 'Slimpd\Controllers\Auth\AuthController:getSignIn')->add($container->get('csrf'))->setName('auth.signin');
$app->post('/auth/signin', 'Slimpd\Controllers\Auth\AuthController:postSignIn');
$app->post('/auth/logout', 'Slimpd\Controllers\Auth\AuthController:postLogout')->setName('auth.logout');
$app->get('/auth/status', 'Slimpd\Controllers\Auth\AuthController:getStatus')->setName('auth.status');
$app->post('/auth/quicksignin', 'Slimpd\Controllers\Auth\AuthController:postQuickSignIn')->setName('auth.quicksignin');

$app->get('[/]', 'Slimpd\Controllers\User\UserController:homeAction')->setName('home');
$app->get('/users/list', 'Slimpd\Controllers\User\UserController:listAction')->setName('users.list');
$app->post('/users/edit', 'Slimpd\Controllers\User\UserController:editAction')->setName('users.edit');