<?php

error_reporting(E_ALL & ~E_DEPRECATED);

if (!file_exists("config.inc.php")) {

    red("ERR: Unable to read config file (filename: config.inc.php)");
    exit(1);

}

if (!file_exists("tags")) {

    red("ERR: Unable to read tags (filename: tags)");
    exit(1);

} else {

    $tags = file("tags", FILE_IGNORE_NEW_LINES);

}

require_once("config.inc.php");         // Configuration file
require_once("adodb5/adodb.inc.php");    // Because ADOdb is the library of choice :)
require_once("lib.inc.php");            // Common functions

// Setup
$db = NewADOConnection('mysql');
$status = @$db->Connect($config["db"]["server"], $config["db"]["username"], $config["db"]["password"], $config["db"]["database"]);

if ($status === FALSE) {

    red("ERR: Unable to connect to the database (check config.inc.php)");
    exit(1);

}

// Let's set the right charset
$db->Execute("SET NAMES 'utf8'");

// select count() considered evil?
$rs = $db->execute("SELECT * FROM pages");
$count = $rs->RecordCount();
bold("Starting with $count row(s) in the database\n");

if ($rs->fields !== FALSE) {

    do {

        $id = $rs->fields["id"];
        $link = $rs->fields["link"];
        $content = $rs->fields["content"];
        $newtags = extractTags($content);
        $count = sizeof($newtags);

        if ($count > 0) {

            $newtags = implode(",", $newtags);
            $db->execute("UPDATE pages SET tags = ? WHERE id = ?", array($newtags, $rs->fields["id"]));
            bullets("Updating $link ($count tag(s) found)");

        }

    } while ($rs->MoveNext());
}

?>
