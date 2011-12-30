<?php

error_reporting(E_ALL & ~E_DEPRECATED);
require_once("simplepie.inc.php");      // For RSS parsing
require_once("simple_html_dom.php");    // For content extraction

// SQLite DB setup
class MyDB extends SQLite3 {

    function __construct() {

        $this->open('scrapper.db');
        $this->exec('CREATE TABLE IF NOT EXISTS pages (id INTEGER, insertion_time INTEGER, site STRING, link STRING, title STRING, html STRING, extracted_content STRING, tags STRING, PRIMARY KEY (id))');

        // select count() considered evil?
        $rs = $this->query("SELECT COUNT(id) FROM pages");
        $val = $rs->fetchArray();
        $val = $val[0];
        echo "Starting with $val row(s) in the database\n\n";

    }
}

$rss = array();
$rss["thestar"] = "http://thestar.com.my/rss/nation.xml";
$rss["tmi"] = "http://allnews.rss.themalaysianinsider.com/c/33362/f/567634/index.rss";
$rss["freemalaysiakini"] = "http://www.freemalaysiakini.com/?feed=rss";
$rss["utusan"] = "http://www.utusan.com.my/utusan/rss.asp";
$rss["merdekareview-malay"] = "http://www.merdekareview.com/bm/rss.php";
$rss["mmail"] = "http://www.mmail.com.my/channel/11/stories/0/feed";
// $rss["btimes"] = "http://www.btimes.com.my/Current_News/BTIMES/rss/rss_html?section=latest"; // Borken XML in their RSS, grrr
// $rss["malaysianmirror"] = ""; // These guys don't have a working RSS feed

$feed = new SimplePie();
$feed->force_fsockopen(true);

$db = new MyDB();
$chkstmt= $db->prepare("SELECT link FROM pages WHERE link = :link");
$insstmt= $db->prepare("INSERT INTO pages (insertion_time, site, link, tags, title, html) VALUES (:time, :site, :link, :tags, :title, :html)");

foreach($rss as $site=>$url) {

    echo "Downloading and parsing feed: $url\n";
    $feed->set_feed_url($url);
    $feed->init();

    foreach ($feed->get_items() as $item) {

        $link = $item->get_id();
        if ($site === "utusan") $link = str_replace("&amp;", "&", $link); // Utusan breaks on completely valid use of &amp; Fscking asp
        else if ($site === "mmail") $link = $item->get_link();

        $chkstmt->bindValue(":link", $link);
        $rs = $chkstmt->execute();
        $vals = $rs->fetchArray();

        if ($vals === false) {

            echo " * Retrieving HTML from $link";

            $html = @file_get_contents($link);

            if ($html === FALSE) {

                echo " - ERR could not retrieve $link\n";
                continue;
            }

            echo "\n";
            $time = time();
            $site = $site;

            $insstmt->bindValue(":time", $time);
            $insstmt->bindValue(":site", $site);
            $insstmt->bindValue(":link", $link);
            $insstmt->bindValue(":title", extractTitle($html));
            $insstmt->bindValue(":html", $html);
            $insstmt->bindValue(":extracted_content", extractContent($site, $html));
            $insstmt->bindValue(":tags", extractTags($html));
            $insstmt->execute();

        } else {

            echo " * Skipping $link\n";
            $rs->finalize();
            continue;

        }
    }

    echo "\n";

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

    $matchedTags = array();
    $html = strtolower($html);
    $tags = array("tony pua", "zahid hamidi", "anwar ibrahim", "muhyiddin yassin", "mahathir mohammad");

    foreach($tags as $tag)
        if (stristr($html, $tag))
            $matchedTags[] = $tag;

    return implode(",", $matchedTags);

}

?>
