<?php

error_reporting(E_ALL & ~E_DEPRECATED);

if (!file_exists("config.inc.php")) {

    red("ERR: Unable to read config file (config.inc.php)");
    exit(1);

}

require_once("config.inc.php");         // Configuration file
require_once("adodb5/adodb.inc.php");    // Because ADOdb is the library of choice :)
require_once("simplepie.inc.php");      // For RSS parsing
require_once("simple_html_dom.php");    // For content extraction

// Setup
$db = NewADOConnection('mysql');
$status = @$db->Connect($config["db"]["server"], $config["db"]["username"], $config["db"]["password"], $config["db"]["database"]);

if ($status === FALSE) {

    red("ERR: Unable to connect to the database (check config.inc.php)");
    exit(1);

}

// select count() considered evil?
$rs = $db->execute("SELECT COUNT(id) AS count FROM pages");
$count = $rs->fields["count"];

h1("Starting with $count row(s) in the database\n");

$rss = array();
$rss["thestar"] = "http://thestar.com.my/rss/nation.xml";
$rss["tmi"] = "http://allnews.rss.themalaysianinsider.com/c/33362/f/567634/index.rss";
$rss["freemalaysiakini"] = "http://www.freemalaysiakini.com/?feed=rss";
$rss["utusan"] = "http://www.utusan.com.my/utusan/rss.asp";
$rss["merdekareview-malay"] = "http://www.merdekareview.com/bm/rss.php";
$rss["mmail"] = "http://mmail.com.my/rss2";
// $rss["btimes"] = "http://www.btimes.com.my/Current_News/BTIMES/rss/rss_html?section=latest"; // Borken XML in their RSS, grrr
// $rss["malaysianmirror"] = ""; // These guys don't have a working RSS feed

$feed = new SimplePie();
$feed->force_fsockopen(true);

$chkstmt= $db->Prepare("SELECT link FROM pages WHERE link = ?");
$insstmt= $db->Prepare("INSERT INTO pages (insertion_time, site, link, tags, title, html) VALUES (?, ?, ?, ?, ?, ?)");

while (TRUE) {

    foreach($rss as $site=>$url) {

        h1("Downloading and parsing feed: $url\n");
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

                if ($html === FALSE) {

                    red("ERR could not retrieve $link");
                    continue;
                }

                $time = time();
                $site = $site;

$insstmt= $db->Prepare("INSERT INTO pages (insertion_time, site, link, tags, title, html) VALUES (?, ?, ?, ?, ?, ?)");
                $db->execute($insstmt, array($time, $site, $link, extractTags($html), extractTitle($html), $html, extractContent($site, $html)));

            } else {

                bullets("Skipping $link");
                continue;

            }
        }

        newline();

    }

    h1("Going to snooze for 15 minutes");
    sleep(15*60);

}

function extractTitle($html) {

    $h = str_get_html($html);
    return trim($h->find("title", 0)->plaintext);

}

function extractContent($site, $html) {

    $h = str_get_html($html);

    if ($site === "thestar") return trim($h->find("#story_content", 0)->plaintext);
    else if ($site === "tmi") return trim($h->find("#article", 0)->plaintext);
    else if ($site === "freemalaysiakini") return trim($h->find("#innerLeft .post div", 0)->plaintext);
    else if ($site === "utusan") return trim($h->find("#ContentContainer", 0)->plaintext);
    else if ($site === "merdekareview-malay") return trim($h->find("#news_content2 td", 0)->plaintext);
    else if ($site === "mmail") return trim($h->find("#content-area", 0)->plaintext);

}

function extractTags($html) {

    // Need to get tags from: https://github.com/Sinar/Kratos/blob/development/db/scraped/members.json
    $matchedTags = array();
    $html = strtolower($html);
    $tags = array("tony pua", "zahid hamidi", "anwar ibrahim", "muhyiddin yassin", "mahathir mohammad");

    foreach($tags as $tag)
        if (stristr($html, $tag))
            $matchedTags[] = $tag;

    return implode(",", $matchedTags);

}

function h1($msg) {
    echo "\033[1m$msg\033[0m\n";
}

function bullets($msg) {
    echo " * $msg\n";
}

function newline() {
    echo "\n";
}

function red($msg) {
    echo "\033[31m$msg\033[30m\n";
}


?>
