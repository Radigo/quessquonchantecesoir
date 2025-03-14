#!/usr/bin/env python3

# build a DB of all available songs

import json
from pathlib import Path
import os
#import re < regexp if necessary
import log

# Folders
#SCRIPT_DIR = Path(__file__).resolve().parent < useful maybe?
APP_DIR = SCRIPT_DIR.parent
SONGS_DIR = "/usr/claude/sync/UltraStar" < check that
# Files
DB_PATH = APP_PATH + "/db.json"

# Read disk
def getDb():
    # TODO: add timer to avoid overloading server
    # serve DB_PATH file if parse is not necessary
    needDbRefresh = True

    if needDbRefresh:
        dbData = dict()

        for root, dirs, files in os.walk(ASSETS_DIR):
            for dir in dirs:
                songData = dict()
                songData["artist"] = "get title from txt file in dir"
                songData["title"] = "get artist name from txt file in dir"
                dbData[dir] = songData

        with open(DB_PATH, 'w', encoding='utf-8') as f:
            json.dump(dbData, f, sort_keys=True, indent=4)

    return DB_PATH;

# Write playlist
def write():