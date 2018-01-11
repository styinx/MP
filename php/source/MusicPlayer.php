<?php

    class MusicPlayer
    {
        public function setupDB()
        {
            require(dirname(__FILE__) . "/../include/config.php");
            require_once(dirname(__FILE__) . "/../include/db/database.php");
            require_once(dirname(__FILE__) . "/../include/dir/directory.php");
            require_once(dirname(__FILE__) . "/../include/tag/tag.php");

            $mp3tag = new MP3Tag();
            $dir = new DIR("");
            $db = new DB($MP_DB["host"], $MP_DB["user"], $MP_DB["pw"], "");

            if(!$db->existsDatabase($MEDIA_DB))
            {
                $db->createDatabase($MEDIA_DB);
                $db->selectDatabase($MEDIA_DB);
            }

            if(!$db->existsTable("session"))
            {
                $db->createTable("session",
                    array("id", "ip", "session"),
                    array("INT(7)", "VARCHAR(100)", "VARCHAR(100)"),
                    array("NOT NULL", "NOT NULL", "NOT NULL"),
                    array("PRIMARY KEY", "", ""),
                    array("", "", ""),
                    array("auto_increment", "", "")
                );
            }

            if(!$db->existsTable("tracks"))
            {
                $db->createTable("tracks",
                    array("id", "filename", "artist", "title", "album", "track", "genre", "year"),
                    array("INT(7)", "VARCHAR(100)", "VARCHAR(100)", "VARCHAR(100)", "VARCHAR(100)", "VARCHAR(100)", "VARCHAR(100)", "VARCHAR(100)"),
                    array("NOT NULL", "NOT NULL", "NOT NULL", "NOT NULL", "NOT NULL", "NOT NULL", "NOT NULL", "NOT NULL"),
                    array("PRIMARY KEY", "", "", "", "", "", "", ""),
                    array("", "", "", "", "", "", "", ""),
                    array("auto_increment", "", "", "", "", "", "", "")
                );
            }

            if($db->selectDatabase($MEDIA_DB))
            {
                foreach($dir->getFilesRecursivly($MUSIC_BASE_PATH, $MUSIC_FORMATS) as $file)
                {
                    $file = str_replace("\\", "/", $file);
                    $tags = $mp3tag->tags($file);
                    $db->insertEntry("tracks",
                        array("filename", "artist", "title", "album", "track", "genre", "year"),
                        array($file, $tags["artist"], $tags["title"], $tags["album"], $tags["track"], $tags["genre"], $tags["year"]));
                }
            }
        }

        public function printLibrary($query = "")
        {
            require(dirname(__FILE__) . "/../include/config.php");
            require_once(dirname(__FILE__) . "/../include/db/database.php");

            $db = new DB($MP_DB["host"], $MP_DB["user"], $MP_DB["pw"], "");
            $table = "";
            if($db->selectDatabase($MEDIA_DB))
            {
                if($query == "")
                {
                    $tracks = $db->queryAssoc("SELECT * FROM tracks ORDER BY artist");
                }
                else
                {
                    $search = "WHERE artist REGEXP '" . $query . ".*' OR "
                        . "title REGEXP '" . $query . ".*' OR "
                        . "album REGEXP '" . $query . ".*'";
                    $tracks = $db->queryAssoc("SELECT * FROM tracks " . $search . " ORDER BY artist");
                }


                $table .= "<thead>"
                    . "<tr>"
                    . "<th class='hidden'></th>"
                    . "<th>Artist</th>"
                    . "<th>Title</th>"
                    . "<th>Album</th>"
                    . "<th>Track</th>"
                    . "<th>Genre</th>"
                    . "<th>Year</th>"
                    . "</tr>"
                    . "</thead>";

                $table .= "<tbody>";
                foreach($tracks as $track)
                {
                    $table .= "<tr class='track' ondbclick=\"MP.loadMedia(this);\" onclick=\"loadMedia(this);\">"
                        . "<td class='hidden file'>" . $track["filename"] . "</td>"
                        . "<td class='artist'>" . htmlentities($track["artist"]) . "</td>"
                        . "<td class='title'>" . htmlentities($track["title"]) . "</td>"
                        . "<td class='album'>" . htmlentities($track["album"]) . "</td>"
                        . "<td class='number'>" . htmlentities($track["track"]) . "</td>"
                        . "<td class='genre'>" . htmlentities($track["genre"]) . "</td>"
                        . "<td class='year'>" . htmlentities($track["year"]) . "</td>"
                        . "</tr>";
                }
                $table .= "</tbody>";
            }
            return $table;
        }
    }
?>