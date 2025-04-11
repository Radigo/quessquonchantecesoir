<?php
require_once '../server/api.php';

foreach (getPlaylists() as $file) {
    var_dump(readPlaylist($file));
}

?>