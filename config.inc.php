<?php

$config = array(

    "db" => array(

        "server"=>"localhost",
        "username"=>"root",
        "password"=>"",
        "database"=>"sinar",

    )
);

$rss = array();
$rss["freemalaysiakini"] = "http://www.freemalaysiakini.com/?feed=rss";
$rss["utusan"] = "http://www.utusan.com.my/utusan/rss.asp";
$rss["merdekareview-malay"] = "http://www.merdekareview.com/bm/rss.php";
$rss["mmail"] = "http://mmail.com.my/rss2";
$rss["bernama"] = "http://www.bernama.com/bernama/v6/rss/english.php";
$rss["thestar"] = "http://thestar.com.my/rss/nation.xml";
$rss["tmi"] = "http://allnews.rss.themalaysianinsider.com/c/33362/f/567634/index.rss";
/*$rss["btimes"] = "http://www.btimes.com.my/Current_News/BTIMES/rss/rss_html?section=latest"; // Borken XML in their RSS, grrr
$rss["malaysianmirror"] = ""; // These guys don't have a working RSS feed*/



?>
