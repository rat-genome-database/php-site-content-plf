<?php
//
// Function to take a parameter and return XML for gviewer
// $Header: /var/lib/cvsroot/Development/RGD/rgdCuration/project/modules/gviewer.php,v 1.5 2007/09/11 20:09:14 gkowalski Exp $
//
// Output XML directly to the browser given the parameters :
// 
// ont_term1 = the Ontology term to search for
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
    pageRequest('{$url}&action=tab', 'gview');
}

function genomeView() {
    pageRequest('{$url}&action=flash', 'gview');
}

function viewMatchingTerms() {
    pageRequest('{$url}&action=terms', 'gview');
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
document.getElementById(divId).innerHTML ="<img src='/common/images/loading_2.gif' />";

}
</script>

HTML;

echo $tmp;
}

function gviewer_getAnnotationData() {
  header('Content-disposition: attachment; filename=rgd-annotation-data.tab');
  header('content-type: application/vnd.ms-excel');
  setDirectOutput("text/xml");
  $dao = new AnnotationSearchDAO();

    $genes = $dao->getGenes();
    $qtls = $dao->getQTLS();
    $strains = $dao->getStrains();

    echo "Chromosome\tRGD ID\tType\tSymbol\tStart Position\tStop Position\tSpecies\n";

    $formatter = new AnnotationFormatter();
    foreach ( $genes as $gene ) {
        extract ( $gene );
        echo $CHROMOSOME . "\t" . $RGD_ID . "\tgene\t" . $GENE_SYMBOL . "\t" . $START_POS . "\t" . $STOP_POS . "\trat\n";
    }
    foreach ( $qtls as $qtl ) {
        extract ( $qtl );
        echo $CHROMOSOME . "\t" . $RGD_ID . "\tqtl\t" . $QTL_SYMBOL . "\t" . $START_POS . "\t" . $STOP_POS . "\trat\n";
    }
    foreach ( $strains as $strain ) {
        extract ( $strain );
        echo  "\t" . $RGD_ID . "\tstrain\t" . $STRAIN_SYMBOL . "\t\t\trat\n";
    }
}

function gviewer_getTermData() {
  header('Content-disposition: attachment; filename=rgd-term-data.tab');
  header('content-type: application/vnd.ms-excel');

  setDirectOutput("text/xml");
  $dao = new AnnotationSearchDAO();
  $terms = $dao->getTerms();

  echo "Term\tOntoloty\tRat\tMouse\tHuman\n";
  foreach ( $terms as $term ) {
      extract ( $term );
      echo $TERM . "\t" . $ONT_NAME . "\t" . $ANNOT_OBJ_CNT_W_CHILDREN_RAT . "\t" . $ANNOT_OBJ_CNT_W_CHILDREN_MOUSE . "\t" . $ANNOT_OBJ_CNT_W_CHILDREN_HUMAN . "\n";
  }
}


function gviewer_getxml() { 

  setDirectOutput("text/xml");

  //valid actions are flash, terms, tab, and xml
  $action='flash';
  $height = 220;
  $width = 700;
  $terms = "";
  $output = "";
  $terms = "";

  $dao = new AnnotationSearchDAO();
  $species=3;

  if (isset($_REQUEST['speciesType'])) {
      $species = $_REQUEST['speciesType'];
  }
 

  $color = ''; 
  if (isset($_REQUEST['color'])) {
     $color=$_REQUEST['color']; 
  } 

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

  $terms = $dao->getTerms();
  $downloadURL = str_replace("func=", "lastfunc=", curPageURL()) . "&func=getTermData";

$tmp = <<< HTML
    <style>
/* Sortable tables */
table.sortable thead {
    background-color:#eee;
    color:#666666;
    font-weight: bold;
    cursor: pointer;
}
  </style>

    <br>
    <table border='0' style='font-size:13;' width='95%' class="sortable" cellspacing="0">
    <thead>
    <tr style="font-weight:700; text-decoration: underline;">
        <td>Term</td>
        <!--<td>Report</td> -->
        <td>Ontology&nbsp;</td>
        <td align="right">Rat</td>
        <td align="right">Mouse</td>
        <td align="right">Human</td>
        <td align="right">Tree</td>
    </tr>
    </thead>
HTML;
echo $tmp;

    $lastTerm = "";
    foreach ( $terms as $term ) {
      extract ( $term );

      $TERM_NAME = $TERM; 

      $TERM = strtolower($TERM);

      if ($lastTerm != $TERM) {
          $lastTerm=$TERM;

          if (isset($_REQUEST['term'])) {
              for ($i=0; $i < count($_REQUEST['term']); $i++) {
                  $_REQUEST['term'][$i] = str_replace("%", "" , $_REQUEST['term'][$i]);
              $TERM = str_replace(strtolower($_REQUEST['term'][$i]), "<b>" . strtolower($_REQUEST['term'][$i]) . "</b>", $TERM);
              }
          }

$tmp = <<< HTML
          <tr>
            <td><a href="javascript:gview().addObjectsByURL('/plf/plfRGD/?module=gviewerStandAlone&func=getAnnotationXML&term_acc={$TERM_ACC}&speciesType={$species}','{$color}','{$TERM_NAME}');void(0);">{$TERM}</a></td>
            <td>{$ONT_NAME}</td>
            <td align="right">{$ANNOT_OBJ_CNT_W_CHILDREN_RAT}</td>
            <td align="right">{$ANNOT_OBJ_CNT_W_CHILDREN_MOUSE}</td>
            <td align="right">{$ANNOT_OBJ_CNT_W_CHILDREN_HUMAN}</td>
            <td align="right"><a target="_blank" href="/rgdweb/ontology/view.html?acc_id={$TERM_ACC}"><img style="border-style: none; border:0;" border="0" src="/common/images/tree2.gif" height=15/></a></td>
		  </tr>
HTML;
		echo $tmp;
      }
    }
    echo "</table>";
 }

function gviewer_getxmlTool() { 

  setDirectOutput("text/xml");

  //valid actions are flash, terms, tab, and xml
  $action='flash';
  $height = 220;
  $width = 700;
  $terms = "";
  $output = "";
  $terms = "";

  $dao = new AnnotationSearchDAO();
  $species=3;

  if (isset($_REQUEST['speciesType'])) {
      $species = $_REQUEST['speciesType'];
  }
 

  $color = ''; 
  if (isset($_REQUEST['color'])) {
     $color=$_REQUEST['color']; 
  } 

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

  $terms = $dao->getTerms();
  $downloadURL = str_replace("func=", "lastfunc=", curPageURL()) . "&func=getTermData";

$tmp = <<< HTML
    <style>
/* Sortable tables */
table.sortable thead {
    background-color:#eee;
    color:#666666;
    font-weight: bold;
    cursor: pointer;
}
  </style>

  <br>
    <table border='0' style='font-size:13;' width='95%' class="sortable" cellspacing="0">
    <thead>
    <tr style="font-weight:700; text-decoration: underline;">
        <td>Term</td>
        <!--<td>Report</td> -->
        <td>Ontology&nbsp;</td>
        <td align="right">Rat</td>
        <td align="right">Mouse</td>
        <td align="right">Human</td>
        <td align="right">Tree</td>
    </tr>
    </thead>
HTML;
	echo $tmp;
    
    $lastTerm = "";
    foreach ( $terms as $term ) {
      extract ( $term );

      $TERM_NAME = $TERM; 

      $TERM = strtolower($TERM);

      if ($lastTerm != $TERM) {
          $lastTerm=$TERM;

          if (isset($_REQUEST['term'])) {
              for ($i=0; $i < count($_REQUEST['term']); $i++) {
                  $_REQUEST['term'][$i] = str_replace("%", "" , $_REQUEST['term'][$i]);
              $TERM = str_replace(strtolower($_REQUEST['term'][$i]), "<b>" . strtolower($_REQUEST['term'][$i]) . "</b>", $TERM);
              }
          }

$tmp = <<< HTML
          <tr>
            <td><a target="_blank" href="/rgdweb/ontology/annot.html?acc_id={$TERM_ACC}">{$TERM}</a></td>
            <td>{$ONT_NAME}</td>
            <td align="right">{$ANNOT_OBJ_CNT_W_CHILDREN_RAT}</td>
            <td align="right">{$ANNOT_OBJ_CNT_W_CHILDREN_MOUSE}</td>
            <td align="right">{$ANNOT_OBJ_CNT_W_CHILDREN_HUMAN}</td>
            <td align="right"><a target="_blank" href="/rgdweb/ontology/view.html?acc_id={$TERM_ACC}"><img style="border-style: none; border:0;" border="0" src="/common/images/tree2.gif" height=15/></a></td></tr>
HTML;
		echo $tmp;
      }
    }
    echo "</table>";
 }

function gviewer_getAnnotationXML() { 

  setDirectOutput("text/xml");
  
  $output = "";
  $GENE_URL = "/rgdweb/report/gene/main.html?id=";
  $QTL_URL  = "/rgdweb/report/qtl/main.html?id=";

  $dao = new AnnotationSearchDAO();

  if (isset($_REQUEST['action'])) {
    $action = $_REQUEST['action'];
  }

    $genes = $dao->getGenes();
    $qtls = $dao->getQTLS();
    $strains = $dao->getStrains();

    //$tabOutput="";
    $formatter = new AnnotationFormatter();
    $output = "<?xml version='1.0' standalone='yes' ?>\n";
    $output .= "<genome>\n";
    foreach ( $genes as $gene ) {
        extract ( $gene );
        $output .= $formatter->toXML($CHROMOSOME,$START_POS,$STOP_POS,"gene",$GENE_SYMBOL,$GENE_URL,"0x79CC3D",$RGD_ID);
    }
    foreach ( $qtls as $qtl ) {
        extract ( $qtl );
        $output .= $formatter->toXML($CHROMOSOME,$START_POS,$STOP_POS,"qtl",$QTL_SYMBOL,$QTL_URL,"0xFFFFFF",$RGD_ID);
    }

    $output .= "</genome>\n";

    return $output;
 }
 
?>
