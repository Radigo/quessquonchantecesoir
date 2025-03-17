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

        <form action="/action_page.php">
            <input type="search" id="search" placeholder="Qu'est-ce qu'on chante ce soir ?">
        </form>

        <article id="main">
            <p>Tapez le nom d'une chanson pour l'ajouter à votre playlist.</p>
            <p>Cliques sur "songs" pour consulter la liste complète (et trouver des idée ?)</p>
            <p>Cliquez sur "playlists" pour gérer vos playlists</p>
            <p>Ne cliquez pas sur "refresh" toutes les 5mn</p>
        </article>
    </body>
    <script>
        const numSubbestionsMax = 10;
        var pageMode = "home";
        // USER INPUTS //
        document.getElementById("search").addEventListener('input', function (evt) {
            if (pageMode === "playlists") {
                // TODO
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
            document.getElementById("main").innerHTML = "";
        }

        function onClickRefresh() {
            window.location.href = '?refresh=1';
        }

        // FILTER (test) //

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

                if (searchToken !== "" && songData.name.toLowerCase().match(searchToken)) {
                    filteredSongs.push(songData);
                }

                console.log(searchToken, songData.name);
                console.log(stringSimilarity(searchToken, songData.name));

                // If there is just a few differences we put the entry in a secondary list for suggestions
                if ((stringSimilarity(searchToken, songData.name) > 0.5)
                    && !filteredSongs.includes(songData)
                    && suggestedSongs.length < numSubbestionsMax) {
                    suggestedSongs.push(songData);
                }
            }

            console.log(filteredSongs);
            console.log(suggestedSongs);

            var resultSongs = document.createDocumentFragment();
            for (const filteredSong of filteredSongs) {
                var newEntry = document.createElement("li");

                newEntry.data_path = filteredSong.path;
                newEntry.data_name = filteredSong.name;
                newEntry.classList.add("result");
                newEntry.appendChild(document.createTextNode(filteredSong.name));
                newEntry.addEventListener("click", function() {
                    clickOnSong(filteredSong.path);
                });
                resultSongs.appendChild(newEntry);
            }
            
            for (const suggestedSong of suggestedSongs) {
                var newEntry = document.createElement("li");

                newEntry.data_path = suggestedSong.path;
                newEntry.data_name = suggestedSong.name;
                newEntry.classList.add("suggestion");
                newEntry.appendChild(document.createTextNode(suggestedSong.name));
                newEntry.addEventListener("click", function() {
                    clickOnSong(suggestedSong.path);
                });
                resultSongs.appendChild(newEntry);
            }

            document.getElementById("main").appendChild(resultSongs);
        }

        // MAIN //
        var dbJson = <?php echo $dbJson; ?>;
    </script>
</HTML>