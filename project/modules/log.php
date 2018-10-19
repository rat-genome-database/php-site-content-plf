<?php

function log_link() {
    setTemplate('default');
    $query = "insert into link_log (link, link_domain, link_date) values ('" . $_REQUEST['link'] . "','"  . parse_url($_REQUEST['link'],PHP_URL_HOST) .  "',to_date('" . date('Y-m-d') . "','YYYY-MM-DD'))";
    echo $query; 
    executeUpdate($query,"POLL");
}

