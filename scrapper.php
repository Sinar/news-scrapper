<?php

error_reporting(E_ALL & ~E_DEPRECATED);

if (!file_exists("config.inc.php")) {

    red("ERR: Unable to read config file (filename: config.inc.php)");
    exit(1);

}

require_once("config.inc.php");         // Configuration file
require_once("adodb5/adodb.inc.php");   // Because ADOdb is the library of choice :)
require_once("simplepie.inc.php");      // For RSS parsing
require_once("simple_html_dom.php");    // For content extraction
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
$rs = $db->execute("SELECT COUNT(id) AS count FROM pages");
$count = $rs->fields["count"];

bold("Starting with $count row(s) in the database\n");

$feed = new SimplePie();
$feed->force_fsockopen(true);

$chkstmt= $db->Prepare("SELECT link FROM pages WHERE link = ?");
$insstmt= $db->Prepare("INSERT INTO pages (insertion_time, site, link, title, content, html) VALUES (?, ?, ?, ?, ?, ?)");

while (TRUE) {

    foreach($rss as $site=>$url) {

        bold("Downloading and parsing feed: $url\n");
        $feed->set_feed_url($url);
        $feed->init();

        foreach ($feed->get_items() as $item) {

            $link = $item->get_id();
            if ($site === "utusan") $link = str_replace("&amp;", "&", $link); // Utusan breaks on completely valid use of &amp; Fscking asp
            else if ($site === "mmail") $link = $item->get_link();

            $rs = $db->execute($chkstmt, array($link));

            if ($rs->fields === FALSE) {

                bullets("Retrieving HTML from $link");

                $html = @file_get_contents($link);

                if ($site === "utusan") $html = iconv('ISO-8859-15', 'UTF-8//IGNORE', $html);
                else $html = iconv('', 'UTF-8//IGNORE', $html);

                if ($html === FALSE) {

                    red(" - ERR could not retrieve $link");
                    errorlog("Could not retrieve link $link");
                    continue;

                }

                $time = time();
                $content = extractContent($site, $html);

                if ($content === FALSE || strlen($content) === 0) {


                    red(" - ERR No content in $link");
                    errorlog("No content in $link");
                    continue;
 
                } else $db->execute($insstmt, array($time, $site, $link, extractTitle($site, $html), $content, $html));

            } else {

                bullets("Skipping $link");
                continue;

            }
        }

        newline();

    }

    bold("Going to snooze for 15 minutes");
    sleep(15*60);

}

?>
