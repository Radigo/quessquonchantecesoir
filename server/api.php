<?php
$songsPath = "/home/jean/Sync/UltraStar/songs";
$playlistsPath = "../target/";
$dbFile = "db.json";
$verbose = false;

// Parsing
function parseDir($forceScan, $limit, $verbose) {
    // Check if scan is allowed
    // Verbose will write in json export, use it only in command line, not when invoked by page
    $GLOBALS["verbose"] = $verbose;

    if ($GLOBALS["verbose"]) var_dump($forceScan, $limit, $verbose);
    $now = time();
    $db = new stdClass;
    $dbStr = "";
    $performScan = true;

    if (file_exists($GLOBALS["dbFile"])) {
        $dbStr = file_get_contents($GLOBALS["dbFile"]);
        $db = json_decode($dbStr);
        
        if ($GLOBALS["verbose"]) echo "not enought time passed since last scan: ";
        if ($GLOBALS["verbose"]) echo date("H:i:s", ($now - $db->last_scan_date));
        if ($GLOBALS["verbose"]) echo (", must wait: ");
        if ($GLOBALS["verbose"]) echo "\n";
        $performScan = false;
    }
    
    if ($performScan || $forceScan) {
        $songsList = [];
        
        getDirContents($GLOBALS["songsPath"], $songsList, $limit);

        $db = new stdClass;
        $db->last_scan_date = time();
        $db->songs = $songsList;

        $dbStr = json_encode($db, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        file_put_contents($GLOBALS["dbFile"], $dbStr, LOCK_EX);
    }

    return $dbStr;
}

function getDirContents($dir, &$songsList, &$limit) {
    if ($limit == 0) {
        return;// Recursive method, we need to return all sub levels as soon as we enter as well
    }
    
    $files = scandir($dir);

    foreach ($files as $key => $value) {
        $path = realpath($dir . DIRECTORY_SEPARATOR . $value);
        
        if (is_dir($path) && ($value != "." && $value != ".." && $value != ".stfolder")) {
            //if ($GLOBALS["verbose"]) echo("Directory ".$value." in ".$dir.", path: ".$path)."\n";
            # Check if this is a song dir
            $dirFiles = scandir($path);

            foreach ($dirFiles as $dirKey => $dirValue) {
                $dirPath = realpath($path . DIRECTORY_SEPARATOR . $dirValue);
                $pathInfo = pathinfo($dirPath);

                if( isset($pathInfo["extension"]) AND strtolower($pathInfo["extension"]) == 'txt') {
                    if ($GLOBALS["verbose"]) echo($limit." > Found song data file ".$dirValue)."\n";
                    $songData = new stdClass;
                    $songData->path = $dirPath;
                    $songData->name = $pathInfo["filename"];

                    array_push($songsList, $songData);
                    
                    $limit--;
                    if ($limit == 0) {
                        return;
                    }
                }
            }

            getDirContents($path, $songsList, $limit);
        }
    }
}

function getPlaylists() {
    $playlists = [];
    
    $files = scandir($GLOBALS["playlistsPath"]);
    foreach ($files as $key => $value) {
        if (is_dir($value)) {
            continue;
        }

        array_push($playlists, $GLOBALS["playlistsPath"].$value);
    }

    return $playlists;
}

/*
function readPlaylist($playlistPath) {
    $playlistData = new stdClass;

    //$plStr = file_get_contents($playlistPath);
    $handle = fopen($playlistPath, "r");

    $isListingSongs = false;

    while (($line = fgets($handle)) !== false) {
        $line = rtrim($line, "\r\n");
        if (strpos($line, "Name:") === 0) {
            $playlistData->name = substr($line, 6);
            continue;
        } else if (strpos($line, "Songs:") === 0) {
            $isListingSongs = true;
            $playlistData->songs = [];
            continue;
        }

        if ($isListingSongs) {
            $songData = new stdClass;
            $songData->artist = substr($line, 0, strpos($line, " : "));
            $songData->title = substr($line, strpos($line, " : ") + 3);
            array_push($playlistData->songs, $songData);
        }
    }

    return $playlistData;
}
*/

/** Commit playlist to target */
function writePlaylist($playlistData) {
    $content = "";

    // HEADER //
    $content .= "######################################\n";
    $content.= "#Ultrastar Deluxe Playlist Format v1.0\n";
    $content.= "#Playlist ".$playlistData->name." with ".count($playlistData->songs)." Songs.\n";
    $content.= "######################################\n";
    $content.= "#Name: ".$playlistData->name."\n";
    // SONGS //
    $content.= "#Songs:\n";
    foreach ($playlistData->songs as $key => $song) {
        $content.= $song->artist." : ".$song->title."\n";//< ok if we don't process the song data
    }
}


# Target example:

#    ######################################
#    #Ultrastar Deluxe Playlist Format v1.0     <- rename this for fun
#    #Playlist TataList with 2 Songs.           <- Change this for fun too
#    ######################################
#    #Name: TataList                            <- Get the name from client choice
#    #Songs:                                    <- static required
#    Hatsune Miku : Ievan Polkka
#    Ricchi & Poveri : Sarà perché ti amo

# Look for #TITLE and #ARTIST fields in txt file of each folder
# #ARTIST : #TITLE 