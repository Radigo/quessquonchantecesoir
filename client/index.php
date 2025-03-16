<?php
if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on')   
    $rootUrl = "https://".$_SERVER['HTTP_HOST']."/index.php";
else  
$rootUrl = "http://".$_SERVER['HTTP_HOST']."/index.php";

// titou => ultra
$valid_passwords = array ("6606b6570a1ef00055c39024a6d53ea6" => "3df5b1fe941899e37bad12a34d8414ae");
$valid_users = array_keys($valid_passwords);

$user = $_SERVER['PHP_AUTH_USER'];
$pass = $_SERVER['PHP_AUTH_PW'];

$validated = (in_array(md5($user), $valid_users)) && (md5($pass) == $valid_passwords[md5($user)]);

if (!$validated) {
  header('WWW-Authenticate: Basic realm="Claude"');
  header('HTTP/1.0 401 Unauthorized');
  die ("Not authorized");
}

// If arrives here, is a valid user.

require_once '../server/api.php';
if (@$_GET["refresh"]) {
    $dbJson = parseDir(true, 1000, false);
    header('Location: '.$rootUrl);
    exit;
} else {
    $dbJson = parseDir(false, 1000, false);
}

?>
<HTML>
    <head>
        <link rel="stylesheet" href="playlist.css">
    </head>
    <body>
        <nav>
            <ol id="menu">
            <li id="showSongs" onClick="onClickSongs()">Songs</li>
            <li id="showPlaylists" onClick="onClickPLaylists()">Playlists</li>
            <li id="refresh" onClick="onClickRefresh()">↺</li>
            </ol>
        </nav>

        <article id="main">Alors ? Qu'est-ce qu'on chante ce soir ?</article>
    </body>
    <script>

        function onClickSongs() {
            document.getElementById("main").innerHTML = "";

            var toAddSongs = document.createDocumentFragment();
            for (const songData of dbJson.songs) {
                var newEntry = document.createElement("li");

                newEntry.data_path = songData.path;
                newEntry.data_name = songData.name;
                newEntry.appendChild(document.createTextNode(songData.name));
                newEntry.addEventListener("click", function() {
                    clickOnYearSong(songData.path);
                });
                toAddSongs.appendChild(newEntry);
            }
            
            document.getElementById("main").appendChild(toAddSongs);
        }
        
        function onClickPLaylists() {
            document.getElementById("main").innerHTML = "";
        }

        function onClickRefresh() {
            window.location.href = '?refresh=1';
        }

        // MAIN //

        var dbJson = <?php echo $dbJson; ?>;
    </script>
</HTML>