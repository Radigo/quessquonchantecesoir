# quessquonchantecesoir
Playlist manager for UltraStar Deluxe

Web interface to manage playlists remotely.

Intended usage is:
- browse songs from mobile device on the go
- select songs to add to a playlist
- once at home launch UltraStar, select the playlist and enjoy

## Setup
3 devices:
- the machine running UltraStar > the 'target'
- the server delivering the page and hosting all the song files > the 'server'
- the client running this manager tool (mobile) > the 'client'

### Target
The machine runs once in a while
It synchronize the song files thanks to SyncThing. We will synchronize the playlist files the same way.

### Server
The machine runs all the time.
It has access to the SyncThing'ed song files locally.
The served page will parse the song files once in a while and produce a json DB.
The back end will serve the json DB to the client.
The back end will produce playlist files which will be placed in another SyncThing'ed folder for the target.

### Client
Web page optimized for mobile.
Browse all the songs from the json DB.
Search, filter, sort features.
Organize playlists and songs within playlists (add, remove, sort).
Nice to have: get stats & scores of songs from UltraStar db file.
