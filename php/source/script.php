<?php
    header("Content-Type: application/x-www-form-urlencoded");

    $response = "";

    if(isset($_REQUEST["request"]))
    {
        require_once(dirname(__FILE__) . "/../include/config.php");
        require_once(dirname(__FILE__) . "/../include/db/database.php");
        require_once(dirname(__FILE__) . "/MusicPlayer.php");

        $music_player = new MusicPlayer();

        if($_REQUEST["request"] == "setupLibrary")
        {
            $music_player->setupDB();
            $response = "init";
        }
        else if($_REQUEST["request"] == "loadLibrary")
        {
            if(isset($_REQUEST["query"]))
                $response = $music_player->printLibrary($_REQUEST["query"]);
            else
                $response = $music_player->printLibrary();
        }
        else if($_REQUEST["request"] == "loadMedia")
        {
            $f = file_get_contents($_REQUEST["media"]);
            $response = base64_encode($f);
        }
        else if($_REQUEST["request"] == "saveSession")
        {
            $db = new DB($MP_DB["host"], $MP_DB["user"], $MP_DB["pw"], "");
            if($db->existsEntry("session", "ip", $_SERVER["REMOTE_ADDR"]))
            {
                $entry = $db->selectEntry("session", "*", array("ip"), array($_SERVER["REMOTE_ADDR"]));
                $db->updateEntry("session", array("ip"), array($_SERVER["REMOTE_ADDR"]), "id", $entry["id"]);
            }
            else
            {
                $db->insertEntry("session", array("ip", "session"), array($_SERVER["REMOTE_ADDR"], $_REQUEST["session"]));
            }
            $response = $db->existsEntry("session", "ip", $_SERVER["REMOTE_ADDR"]);
        }
        else if($_REQUEST["request"] == "loadSession")
        {
            $db = new DB($MP_DB["host"], $MP_DB["user"], $MP_DB["pw"], "");
            $entry = $db->selectEntry("session", "*", "ip", $_SERVER["REMOTE_ADDR"]);
            if(!empty($entry))
                $response = $entry["session"];
        }
    }

    echo $response;
?>