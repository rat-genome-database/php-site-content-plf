<?php
//
// Function to take a parameter and return XML for gviewer
// $Header: /var/lib/cvsroot/Development/RGD/rgdCuration/project/modules/gviewer.php,v 1.5 2007/09/11 20:09:14 gkowalski Exp $
//
// Output XML directly to the browser given the parameters :
// 
// term_acc = acc of the Ontology term to search for
// Generates anootation data per the spec found here : 
// http://blog.gmod.org/nondrupal/FlashGViewer_forWeb/index.html
// 

require_once 'project/modules/BrowserChecker.php';
require_once 'project/modules/AnnotationSearchDAO.php';
require_once 'project/modules/AnnotationFormatter.php';

//returns the url and query string for the current page
function curPageURL() {
 $pageURL = 'http://' . $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
 return $pageURL;
}

//output javascript used by the page
function writeScript($url) {
$tmp = <<< HTML

<script type="text/javascript" language="javascript">

// Error: uncaught exception: Permission denied to call method XMLHttpRequest.open

function downloadAnnotationData() {
    pageRequest('${url}&action=tab', 'gview');
}

function genomeView() {
    pageRequest('${url}&action=flash', 'gview');
}

function viewMatchingTerms() {
    pageRequest('${url}&action=terms', 'gview');
}

var http_request = false;
function pageRequest(url, divId) {
http_request = false;
if (window.XMLHttpRequest) // if Mozilla, Safari etc
    http_request = new XMLHttpRequest()
else if (window.ActiveXObject){ // if IE
    try {
        http_request = new ActiveXObject("Msxml2.XMLHTTP")
    } catch (e){
        try{
            http_request = new ActiveXObject("Microsoft.XMLHTTP")
        } catch (e){}
    }
}
if (http_request.overrideMimeType) {
  http_request.overrideMimeType('text/xml');
}
if (!http_request) {
  alert('Cannot create XMLHTTP instance');
  return false;
}
//http_request.onreadystatechange = alertContents;
http_request.onreadystatechange = function () {
      document.getElementById(divId).innerHTML = "";
    if (http_request.readyState == 4) {
      if (http_request.status == 200) {
        //renderGviewer(http_request.responseText);
        document.getElementById(divId).innerHTML =http_request.responseText;
      } else {
        alert(http_request.responseText);
        alert('There was a problem with the request.');
      }
    }
}
http_request.open('GET', url, true);
http_request.send(null);
document.getElementById(divId).innerHTML ="<span style='font-size:11; font-weight:700;'>Loading</span><br><img src='/common/images/loading_2.gif' />";

}
</script>

HTML;

echo $tmp;
}


function gviewerStandAlone_getAnnotationXML() {
  header('Content-disposition: attachment; filename=rgd-annotation-data.xml');
  setDirectOutput("text/xml");

  // if term_acc param is not available, then we got request from unexpected old tool
  // just return empty xml contents
  if (!isset($_REQUEST['term_acc'])) {
    echo "<?xml version='1.0' standalone='yes' ?><genome></genome>";
	return;
  }

  $dao = new AnnotationSearchDAO();
 
  $species = 3;
   
  if (isset($_REQUEST['speciesType'])) {
     $species = $_REQUEST['speciesType'];
  }
   echo $dao->getXML($_REQUEST['term_acc'], $species);
}


function gviewerStandAlone_getxml() { 

  setDirectOutput("text/xml");

  //valid actions are flash, terms, tab, and xml
  $action='flash';
  $height = 220;
  $width = 700;
  $terms = "";
  $GENE_URL = "/rgdweb/report/gene/main.html?id=";
  $QTL_URL  = "/rgdweb/report/qtl/main.html?id=";
  $output = "";
  $terms = "";
  $totalGenes = 0;
  $totalQTLS = 0;

  $dao = new AnnotationSearchDAO();

  if (isset($_REQUEST['action'])) {
    $action = $_REQUEST['action'];
  }
  //get the height
  if (isset($_REQUEST["h"])) {
    $height = $_REQUEST["h"];
  }
  //get the width
  if (isset($_REQUEST["w"])) {
    $width = $_REQUEST["w"];
  }

    //$tabOutput="";
    $output = "";
    if (isset($_REQUEST['term_acc'])) {
        $output = $dao->getXML($_REQUEST['term_acc']);
        //echo $output;
    }else {

    $genes = $dao->getGenes();
    $qtls = $dao->getQTLS();

        $formatter = new AnnotationFormatter();
        $output = "<?xml version='1.0' standalone='yes' ?>\n";
        $output .= "<genome>\n";
        foreach ( $genes as $gene ) {
            extract ( $gene );
            $output .= $formatter->toXML($CHROMOSOME,$START_POS,$STOP_POS,"gene",$GENE_SYMBOL,$GENE_URL,"0x79CC3D",$RGD_ID);
            $totalGenes++;
        }
        foreach ( $qtls as $qtl ) {
            extract ( $qtl );
            $output .= $formatter->toXML($CHROMOSOME,$START_POS,$STOP_POS,"qtl",$QTL_SYMBOL,$QTL_URL,"0xFFFFFF",$RGD_ID);
            $totalQTLS++;
        }
        $output .= "</genome>\n";
    }
    $downloadURL = "/plf/plfRGD/?module=gviewerStandAlone&func=getAnnotationXML&term_acc=" . $_REQUEST['term_acc'];

    echo "<br><div id='gviewer' style='border: 2px groove grey; width:570;'>";

    $check = new BrowserChecker();
        if ($check->browser_detection( 'browser' ) == "ie") {
            echo '<object classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" codebase="http://fpdownload.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=7,0,0,0" width="' . $width . '" height="' . $height . '" id="GViewer2" align="middle">';
            echo '<param name="allowScriptAccess" value="sameDomain" />';
            echo '<param name="movie" value="/GViewer/GViewer2.swf" />';
            echo '<param name="quality" value="high" />';
            echo '<param name="bgcolor" value="#FFFFFF" />';
            echo '<param name="FlashVars" value="&baseMapURL=/GViewer/data/rgd_rat_ideo.xml&annotationXML=' . urlencode($output);
            echo '&titleBarText=Rat GViewer';
            echo '&browserURL=http://genome.ucsc.edu/cgi-bin/hgTracks?org=Rat%26position=Chr&">';
            echo '</object>';
        }else {
            echo '<embed src="/GViewer/GViewer2.swf" quality="high" bgcolor="#FFFFFF" width="' . $width . '"';
            echo 'height="' . $height . '" name="GViewer2" align="middle" allowScriptAccess="sameDomain" type="application/x-shockwave-flash" FlashVars="&baseMapURL=/GViewer/data/rgd_rat_ideo.xml';
            echo '&annotationXML=' . urlencode($output) . '&titleBarText=Rat GViewer';
            echo '&browserURL=http://genome.ucsc.edu/cgi-bin/hgTracks?org=Rat%26position=Chr&" pluginspage="http://www.macromedia.com/go/getflashplayer" />';
        }

    echo '</div>';
    echo '<table width="570"><tr><td align="right"><a href="'.$downloadURL.'">Download Annotation Data</a></td></tr></table>';

    //header('content-type: application/vnd.ms-excel');
   //$output = "chromosome\trgd_id\ttype\tsymbol\tstart position\tstop position\tspecies\n";
 }

?>
