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
            <form>
                <input type="search" id="search" placeholder="Qu'est-ce qu'on chante ce soir ?">
            </form>

            <ol id="menu">
            <li id="btnSongs" onClick="onClickSongs()">Songs</li>
            <li id="btnPlaylists" onClick="onClickPLaylists()">Playlists</li>
            <li id="refresh" onClick="onClickRefresh()">↺</li>
            </ol>
        </nav>

        <article id="main">
            <p>Tapez le nom d'une chanson pour l'ajouter à votre playlist.</p>
            <p>Cliquez sur "Songs" pour consulter la liste complète (et trouver des idée ?)</p>
            <p>Cliquez sur "Playlists" pour gérer vos playlists</p>
            <p>Ne cliquez pas sur "refresh" toutes les 5mn</p>
        </article>
    </body>
    <script>
        const numSuggestionsMax = 10;
        var pageMode = "home";
        var activePlaylist = null;

        // USER INPUTS //

        document.getElementById("search").addEventListener('input', function (evt) {
            if (pageMode === "playlists") {
                // TODO or nothing
            } else {
                // Default
                displaySongs();
            }
        });

        function onClickSongs() {
            pageMode = "songs";
            displaySongs();
        }
        
        function onClickPLaylists() {
            pageMode = "playlists";
            displayPlaylists();
        }

        function onClickRefresh() {
            window.location.href = '?refresh=1';
        }

        // FILTER //

        /** A result near 1 is a perfect match, near 0 is a poor match */
        function stringSimilarity(strToken, strTest, caseSensitive = false) {
            substringLength = strToken.length * 0.75;

            if (!caseSensitive) {
                strToken = strToken.toLowerCase();
                strTest = strTest.toLowerCase();
            }

            if (strToken.length < substringLength || strTest.length < substringLength)
                return 0;

            const map = new Map();
            for (let i = 0; i < strToken.length - (substringLength - 1); i++) {
                const substr1 = strToken.substr(i, substringLength);
                map.set(substr1, map.has(substr1) ? map.get(substr1) + 1 : 1);
            }

            let match = 0;
            for (let j = 0; j < strTest.length - (substringLength - 1); j++) {
                const substr2 = strTest.substr(j, substringLength);
                const count = map.has(substr2) ? map.get(substr2) : 0;
                if (count > 0) {
                    map.set(substr2, count - 1);
                    match++;
                }
            }

            // Absolute similarity regardless the length of both strings
            //return (match * 2) / (str1.length + str2.length - ((substringLength - 1) * 2));

            // Crop tested string to fit token
            return (match * 2) / (strToken.length - ((substringLength - 1) * 2));
        }

        function isInPlaylistAlready(songData) {
            return true;
        }

        // DISPLAY //
        function displaySongs() {
            // Refresh main
            document.getElementById("main").innerHTML = "";

            var filteredSongs = [];
            var suggestedSongs = [];
            for (const songData of dbJson.songs) {
                // Filter from search input

                // Evaluate difference between search input value and entry name
                const searchToken = document.getElementById("search").value.toLowerCase();

                if (searchToken === "" || songData.name.toLowerCase().match(searchToken)) {
                    filteredSongs.push(songData);
                }

                //console.log(searchToken, songData.name);
                //console.log(stringSimilarity(searchToken, songData.name));

                // If there is just a few differences we put the entry in a secondary list for suggestions
                if ((stringSimilarity(searchToken, songData.name) > 0.5)
                    && !filteredSongs.includes(songData)
                    && suggestedSongs.length < numSuggestionsMax) {
                    suggestedSongs.push(songData);
                }
            }

            //console.log(filteredSongs);
            //console.log(suggestedSongs);

            var resultSongs = document.createDocumentFragment();
            for (const filteredSong of filteredSongs) {
                resultSongs.appendChild(createSongEntry(filteredSong, false));
            }
            
            for (const suggestedSong of suggestedSongs) {
                resultSongs.appendChild(createSongEntry(suggestedSong, true));
            }

            document.getElementById("main").appendChild(resultSongs);
        }

        function createSongEntry(songData, isSuggestion) {
            var newEntry = document.createElement("li");
            // Data
            newEntry.data_path = songData.path;
            newEntry.data_name = songData.name;
            // Overall style
            if (isSuggestion) {
                newEntry.classList.add("suggestion");
            } else {
                newEntry.classList.add("result");
            }

            // Content
            var title = document.createElement("div");
            title.appendChild(document.createTextNode(songData.name));
            title.classList.add("title");
            newEntry.appendChild(title);

            if (isInPlaylistAlready(songData)) {
                var checkMark = document.createElement("div");
                checkMark.appendChild(document.createTextNode("V"));
                checkMark.classList.add("checkMark");
                newEntry.appendChild(checkMark);
            }

            // Actions
            var addButton = document.createElement("div");
            addButton.appendChild(document.createTextNode("+"));
            addButton.classList.add("addButton");
            newEntry.appendChild(addButton);

            return (newEntry);
        }

        /** Retrieve only the list of files, no parsing whatsoever here */
        function displayPlaylists() {
            // Refresh main
            document.getElementById("main").innerHTML = "";

            var playlistEntries = document.createDocumentFragment();
            for (const path of playlistsJson) {
                playlistEntries.appendChild(createPlaylistEntry(path));
            }

            document.getElementById("main").appendChild(playlistEntries);
        }

        /** Set a playlist as active, any ADD song will be put there */
        function selectPlaylist(playlist) {
            console.log("select playlist:", playlist);
            // Create an object to add songs to
            activePlaylist = {};
            playlistFile = new File([], playlist);

            // Read from file
            var reader = new FileReader();
            reader.onload = function(progressEvent) {
                console.log(playlistFile.name);
                console.log(reader.result);
                console.log(reader.error);

                // By lines
                var lines = reader.result.split('\n');
                for (var line = 0; line < lines.length; line++) {
                    console.log(lines[line]);
                }
            };
            reader.readAsText(playlistFile);

            document.getElementById("btnPlaylists").textContent = activePlaylist.title;
        }

        function createPlaylistEntry(playlistPath) {
            var newEntry = document.createElement("li");
            // Data
            newEntry.data_path = playlistPath;
            newEntry.data_name = playlistPath;

            // Content
            var title = document.createElement("div");
            title.appendChild(document.createTextNode(playlistPath));
            title.classList.add("title");
            newEntry.appendChild(title);

            // Actions
            var addButton = document.createElement("div");
            addButton.appendChild(document.createTextNode("Select"));
            addButton.classList.add("selectButton");
            addButton.addEventListener('click', function (evt) {
                selectPlaylist(newEntry.data_path);
            });
            newEntry.appendChild(addButton);

            return newEntry
        }

        // MAIN //
        var dbJson = <?php echo $dbJson; ?>;
        var playlistsJson = <?php echo json_encode(getPlaylists()); ?>;
    </script>
</HTML>