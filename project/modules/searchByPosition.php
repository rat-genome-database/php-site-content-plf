<?php
// setTemplate('searchByPosition');
ini_set("memory_limit", "64M");

function initQueries() {
  $count_objects_in_region_sql = <<<SQL
SELECT 
  o.OBJECT_NAME,
  count(*)
FROM
  RGD_OBJECTS o,
  MAPS_DATA m,
  RGD_IDS r
WHERE m.RGD_ID = r.RGD_ID
AND   r.OBJECT_KEY = o.OBJECT_KEY
AND   m.MAP_KEY = 60 -- RGSC genome assembly v3.4
AND   r.SPECIES_TYPE_KEY = 3 -- Rat only
AND   r.OBJECT_STATUS = 'ACTIVE'
AND   o.OBJECT_NAME IN ('GENES', 'QTLS', 'SSLPS')
AND   m.CHROMOSOME = :chromosome
AND   m.START_POS <= :stop_pos
AND   m.STOP_POS >= :start_pos
AND   m.START_POS < m.STOP_POS -- work around to exclude QTLs with inverted positions
GROUP BY 
  o.OBJECT_NAME  
SQL;

$get_max_stop_pos_sql = <<<SQL
SELECT 
  max(m.STOP_POS) as max_stop
FROM
  RGD_OBJECTS o,
  MAPS_DATA m,
  RGD_IDS r
WHERE m.RGD_ID = r.RGD_ID
AND   r.OBJECT_KEY = o.OBJECT_KEY
AND   m.MAP_KEY = 60 -- RGSC genome assembly v3.4
AND   r.SPECIES_TYPE_KEY = 3 -- Rat only
AND   r.OBJECT_STATUS = 'ACTIVE'
AND   o.OBJECT_NAME IN ('GENES', 'QTLS', 'SSLPS')
AND   m.CHROMOSOME = :chromosome
AND   m.STOP_POS IS NOT NULL

SQL;



  $retrieve_genes_in_region_select_clause = <<<SQL
SELECT
  r.RGD_ID as rgd_id,
  o.OBJECT_NAME as type,
  g.GENE_SYMBOL as symbol,
  g.FULL_NAME as name,
  m.CHROMOSOME as chromosome,
  m.START_POS as start_pos,
  m.STOP_POS as stop_pos
FROM
  RGD_IDS r,
  RGD_OBJECTS o,
  GENES g,
  MAPS_DATA m
WHERE m.RGD_ID = r.RGD_ID
AND   r.RGD_ID = g.RGD_ID
AND   r.OBJECT_KEY = o.OBJECT_KEY
AND   m.MAP_KEY = 60 -- RGSC genome assembly v3.4
AND   r.SPECIES_TYPE_KEY = 3 -- Rat only
AND   o.OBJECT_NAME = 'GENES'
AND   m.CHROMOSOME = :chromosome
AND   m.START_POS <= :stop_pos
AND   m.STOP_POS >= :start_pos
AND   r.OBJECT_STATUS = 'ACTIVE'
SQL;

  $retrieve_genes_in_region_sql =  $retrieve_genes_in_region_select_clause . 
                                   " ORDER BY m.START_POS";

  $retrieve_qtls_in_region_select_clause = <<<SQL
SELECT
  r.RGD_ID as rgd_id,
  o.OBJECT_NAME as type,
  q.QTL_SYMBOL as symbol,
  q.QTL_NAME as name,
  m.CHROMOSOME as chromosome,
  m.START_POS as start_pos,
  m.STOP_POS as stop_pos
FROM
  RGD_IDS r,
  RGD_OBJECTS o,
  QTLS q,
  MAPS_DATA m
WHERE m.RGD_ID = r.RGD_ID
AND   r.RGD_ID = q.RGD_ID
AND   r.OBJECT_KEY = o.OBJECT_KEY
AND   m.MAP_KEY = 60 -- RGSC genome assembly v3.4
AND   r.SPECIES_TYPE_KEY = 3 -- Rat only
AND   o.OBJECT_NAME = 'QTLS'
AND   m.CHROMOSOME = :chromosome
AND   m.START_POS <= :stop_pos
AND   m.STOP_POS >= :start_pos
AND   m.START_POS < m.STOP_POS -- work around to exclude QTLs with inverted positions
AND   r.OBJECT_STATUS = 'ACTIVE'
SQL;
    
  $retrieve_qtls_in_region_sql = $retrieve_qtls_in_region_select_clause . 
                                   " ORDER BY m.START_POS";
    
  $retrieve_sslps_in_region_select_clause = <<<SQL
SELECT
  r.RGD_ID as rgd_id,
  o.OBJECT_NAME as type,
  s.RGD_NAME as symbol,
  s.RGD_NAME as name,
  m.CHROMOSOME as chromosome,
  m.START_POS as start_pos,
  m.STOP_POS as stop_pos
FROM
  RGD_IDS r,
  RGD_OBJECTS o,
  SSLPS s,
  MAPS_DATA m
WHERE m.RGD_ID = r.RGD_ID
AND   r.RGD_ID = s.RGD_ID
AND   r.OBJECT_KEY = o.OBJECT_KEY
AND   m.MAP_KEY = 60 -- RGSC genome assembly v3.4
AND   r.SPECIES_TYPE_KEY = 3 -- Rat only
AND   o.OBJECT_NAME = 'SSLPS'
AND   m.CHROMOSOME = :chromosome
AND   m.START_POS <= :stop_pos
AND   m.STOP_POS >= :start_pos
AND   r.OBJECT_STATUS = 'ACTIVE'
SQL;
  $retrieve_sslps_in_region_sql = $retrieve_sslps_in_region_select_clause . 
                                  " ORDER BY m.START_POS";

  $retrieve_all_objects_in_region_sql = <<<SQL
SELECT * 
FROM 
  ($retrieve_genes_in_region_select_clause
  UNION
  $retrieve_qtls_in_region_select_clause
  UNION
  $retrieve_sslps_in_region_select_clause)
ORDER BY start_pos
SQL;

   
  $queries = array(
      'count_objects_in_region' => prepare($count_objects_in_region_sql),
      'retrieve_genes_in_region' => prepare($retrieve_genes_in_region_sql),
      'retrieve_qtls_in_region' => prepare($retrieve_qtls_in_region_sql),
      'retrieve_sslps_in_region' => prepare($retrieve_sslps_in_region_sql),
      'retrieve_all_objects_in_region' => prepare($retrieve_all_objects_in_region_sql),
      'get_max_stop_pos' => prepare($get_max_stop_pos_sql)); 
     
    
  return($queries);
}

function getQuery($name) {
  $queries = initQueries();
  return($queries[$name]);  
}

function getChromosomes() {
  # set up chromosomes, 1-20, X, Y, MT (mitochondrial)
  foreach (array_merge(range(1,20), array("X", "Y", "MT")) as $chr) {
  # should make keys match the values
      $sbp_chromosomes[$chr] = $chr;
  }
  return $sbp_chromosomes;
}

function renderForm($theForm) {
  $formHTML = <<<FORM
    {$theForm->formStart()}
  <h3 align="center"> Search for genes, SSLPs and QTLs by position </h3>  
  <table class="searchByPositionForm" align="center">
    <tr valign="top">
      <td>{$theForm->renderLabel('chromosome')}</td>
      <td>{$theForm->renderField('chromosome')}</td>
      <td align="right">{$theForm->renderLabel('start_pos')}</td>
      <td>{$theForm->renderField('start_pos')} (bp)</td>
      <td align="right">{$theForm->renderLabel('stop_pos')}</td>
      <td>{$theForm->renderField('stop_pos')} (bp)</td>
      <td>{$theForm->formEnd()}</td>
    </tr>
    <tr>
      <td></td>
    </tr>
    <tr>
      <td colspan="2"></td>
      <td colspan="4" align="center" style="font-size:9pt">(for Mbp use exponential notation, e.g. 30e6 = 30 Mbp)</td></tr>
  </table>
<br>


FORM;

  return($formHTML);  
}
function searchByPosition_form () {
  $toReturn = '<div id="top"><br></div>';
  
  $chromosomes = getChromosomes();
  
  $theForm = newForm('Search', 'GET', 'searchByPosition', 'form');

  $theForm->addSelect('chromosome', 'Chr:', $chromosomes, true);
  $theForm->setDefault('chromosome', 1);
  # maximum position in database for RGSC rat assembly 3.4 is 334,399,858 , 
  # pow(2,29) is 536,870,912, giving us a bit of slack
  $theForm->addNumber('start_pos', 'From:', 1, pow(2,29), false);
  $theForm->addNumber('stop_pos', 'To:', 1, pow(2,29), false);
  
  
  switch ($theForm->getState()) {
  case INITIAL_GET:
  case SUBMIT_INVALID:
    $toReturn .= renderForm($theForm);
    //$toReturn .= $theForm->quickRender();
   
    break;
  case SUBMIT_VALID:
    $toReturn .= renderForm($theForm);
    //$toReturn .= $theForm->quickRender();
  
    if('' !== getRequestVarString('doCVS')) {
      exportCSV($theForm);
      return;
    }
    
    $table = buildSummaryTable($theForm);
    $haveGenes = $table->rowsArray[0][1] > 0;
    $haveSSLPs = $table->rowsArray[1][1] > 0;
    $haveQTLs = $table->rowsArray[2][1] > 0;
    
    $haveMoreThanOneType = $haveGenes + $haveSSLPs + $haveQTLs > 1;
    
    $toReturn .= toScreenSummaryTable($table, $theForm); 
                                 
    $toReturn .= '<br>';
    
    if($haveGenes) {
      $table = buildObjectTable('retrieve_genes_in_region', $theForm, true);
      $toReturn .= toScreenObjectTable($table, 'retrieve_genes_in_region', $theForm);
      $toReturn .= '<br>';
    }
    if($haveSSLPs) {
      $table = buildObjectTable('retrieve_sslps_in_region', $theForm, true);
      $toReturn .= toScreenObjectTable($table, 'retrieve_sslps_in_region', $theForm);
      $toReturn .= '<br>';
    }
    if($haveQTLs) {
      $table = buildObjectTable('retrieve_qtls_in_region', $theForm, true);
      $toReturn .= toScreenObjectTable($table, 'retrieve_qtls_in_region', $theForm);
      $toReturn .= '<br>';
    }
    if($haveMoreThanOneType){
      $table = buildObjectTable('retrieve_all_objects_in_region', $theForm, true);
      $toReturn .= toScreenObjectTable($table, 'retrieve_all_objects_in_region', $theForm);
      $toReturn .= '<br>';
    }
            
                                       
      
    
    break;
  }
  return $toReturn;
}


function exportCSV($theForm){
  
  setDirectOutput();
  
  $query = getRequestVarString('doCVS');
  $table = buildObjectTable($query, $theForm);
  echo $table->toCSV();
  
}

function getPositionQueryParams($theForm){
  $params = array_intersect_key($theForm->getValuesForDb(),array('chromosome' => '',
                                                              'start_pos' => '',
                                                              'stop_pos' => ''));
                                                              
  if($params["start_pos"] === '') {
    $params["start_pos"] = 1;
  }
  if($params["stop_pos"] === '') {
    $rs = executePrepared(getQuery('get_max_stop_pos'),     
                          array('chromosome'=>$params['chromosome']));
    $params['stop_pos'] = $rs->Fields('MAX_STOP');
  }

  return($params);
   
}

function buildSummaryTable($theForm) {
  $counts = array('GENES'=>0,
                  'SSLPS'=>0,
                  'QTLS'=>0);
  $genes = 0;
  $sslps = 0;
  $qtls = 0;
  
  $params = getPositionQueryParams($theForm);
  $rs = executePrepared(getQuery('count_objects_in_region'), $params);
  foreach($rs->GetRows() as $row){
    $counts[$row['OBJECT_NAME']] = $row['COUNT(*)'];          
  }
  
  $counts['ALL TYPES'] = array_sum($counts);
  
  $table = newTable(array("OBJECT TYPE", "COUNT"));
  $table->setAttributes('class="searchByPositionSummary"');
  $table->setDoColumnStyling(true);
  $table->setColumnCSSClasses("object_type", "count");
  $table->setCaption("Objects returned by your search");
  
  $count_all = array_splice($counts, -1);
  foreach($counts as $key => $value) {
    // Don't make an internal link if no objects of a given type are found.
    if($value > 0) {
      $table->addRow('<a href="#'.$key.'">'.$key.'</a>', $value);
    } 
    else {
      $table->addRow($key, $value);
    }
  }
  $haveGenes = $table->rowsArray[0][1] > 0;
  $haveSSLPs = $table->rowsArray[1][1] > 0;
  $haveQTLs = $table->rowsArray[2][1] > 0;
  $haveMoreThanOneType = $haveGenes + $haveSSLPs + $haveQTLs > 1;
  
  // make the all types row an internal link only if there is more than one type 
  if($haveMoreThanOneType) {
    $table->addRow('<a href="#ALL TYPES">ALL TYPES</a>',$count_all['ALL TYPES']);
  }
  else{
    $table->addRow('ALL TYPES',$count_all['ALL TYPES']);
  }
  
  return($table);
}

function toScreenSummaryTable($table, $theForm) {
    
  $toReturn = "";
  
  $caption = "Objects&nbsp;returned&nbsp;by&nbsp;your&nbsp;search.";
  $table->setCaption($caption);
  $toReturn .= $table->toHTML();
  return($toReturn);
}

function trimTypeFilter(&$row) {
  #remove the last 'S' from GENES, QTLS, SSLPS
  #note oracle always returns column names in uppercase
  $row['TYPE'] = substr($row['TYPE'],0,-1);
}

function makeWebResultsFilter(&$row) {
  
  $root = '';
       
  #http://rgd.mcw.edu/tools/genes/genes_view.cgi?id=1349257
  #http://rgd.mcw.edu/objectSearch/qtlReport.jsp?rgd_id=1549835
  #http://rgd.mcw.edu/objectSearch/sslpReport.jsp?rgd_id=10333
 
  switch($row['TYPE']){
    case 'GENE':
    $row['RGD_ID']=makeExternalLink($row['RGD_ID'], 
                    $root. '/tools/genes/genes_view.cgi?id='.$row['RGD_ID']);
    break;
  
    case 'SSLP':
    $row['RGD_ID']=makeExternalLink($row['RGD_ID'],
                    $root. '/objectSearch/sslpReport.jsp?rgd_id='.$row['RGD_ID']);
    break;
  
    case 'QTL':
    $row['RGD_ID']=makeExternalLink($row['RGD_ID'],
                    $root. '/objectSearch/qtlReport.jsp?rgd_id='.$row['RGD_ID']);
    break;
 }
}

function toScreenObjectTable($table, $query, $theForm) {
  if(!isset($table)){
    return;
  }
  
  $captions = array(
      'retrieve_genes_in_region' =>"Genes found in your search region",
      'retrieve_qtls_in_region' => "QTLs found in your search region",
      'retrieve_sslps_in_region' => "SSLPs found in your search region",
      'retrieve_all_objects_in_region' => "Genes, QTLs and SSLPs found in your search region");
      
  $anchors = array(
      'retrieve_genes_in_region' =>"GENES",
      'retrieve_qtls_in_region' => "QTLS",
      'retrieve_sslps_in_region' => "SSLPS",
      'retrieve_all_objects_in_region' => "ALL TYPES");
      
  if(!array_key_exists("doCVS", $theForm->getAllValues())){
    $theForm->addHidden('doCVS', $query);
  }
  else{
    $theForm->setDefault('doCVS', $query);
  }
  $table->setAttributes('class="searchByPositionResults" id="'.$anchors[$query].'"');
  $table->setCaption($captions[$query].' ('.makeLink('export table to spreadsheet', 'searchByPosition', 
                                                'form', $theForm->getAllValues()).')');
  
  $toReturn = $table->toHTML();
  $toReturn .= '<br><a href="#top">Back to top of page</a>';
  return($toReturn); 
}

function buildObjectTable($query_name, $theForm, $makeRGD_IDsLinks = false) {
  
  $header_names = array("RGD_ID", "TYPE", "SYMBOL", "NAME", "CHR.", "START", "STOP");
  $rs = executePrepared(getQuery($query_name), getPositionQueryParams($theForm));
  $rows = $rs->GetRows();
  
  if (0 == count($rows)){
    return;
  }
  
  array_filter($rows, 'trimTypeFilter');
  if($makeRGD_IDsLinks) {
    array_filter($rows, 'makeWebResultsFilter');
  }
  
  $table = newTable($header_names);
  $table->setDoColumnStyling(true); 
  $table->setColumnCSSClasses(array('rgd_id', 'type', 'symbol', 'name', 'chromosome', 'start', 'stop'));
  
  foreach ($rows as $row) {
    $table->addRow($row);        
  }
  return($table);
}

/*
function makeWebDisplayObjectTable($query_name, $fields, $header_row){
  $rs = executePrepared(getQuery($query_name), $fields);
  $rows = $rs->GetRows();
  
  if (0 == count($rows)){
    return;
  }
  
  $table = newTable($header_row);
  array_filter($rows, 'makeWebResultsFilter');
  $table->setAttributes('class="searchByPositionResults"');
  $table->setDoColumnStyling(true); 
  $table->setColumnCSSClasses(array('rgd_id', 'type', 'symbol', 'name', 'chromosome', 'start', 'stop'));
  
  foreach ($rows as $row) {
    $table->addRow($row);        
  }
  return $table->toHTML();
}
*/

?>