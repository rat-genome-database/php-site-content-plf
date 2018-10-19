<?php
define('NOTLOGGEDIN_MSG', 'Please login using the form at the left');
define('NOACCESS_MSG', 'Sorry, you do not have access to this area');

define ('ONT_BUCKET_PREFIX', 'ORPH_');

/**
 * Returns Bread Crumb HTML box at top of form given the string to display
 */
 function makeBreadCrumbLink( $breadCrumbString ){
  
  $toReturn = '<table cellpadding="5" cellspacing="5" border="0" width="100%">'.
        '<tr>' .
        '<td><div width="100%" class="breadcrumbs">'. 
            '&nbsp; Location: '. $breadCrumbString .'</div>
        </td>' .
       '</tr></table>';
      return $toReturn; 
 }
/** 
 * Handles cleanup and adding of search terms for database queries. 
 */
function cleanUpAndHandleMatching($searchTerm, $matchType) {
  $searchTerm = trim($searchTerm);
  switch ($matchType) {
    case 'equals' :
      break;
    case 'contains' :
      $searchTerm = "%" . $searchTerm . "%";
      break;
    case 'begins' :
      $searchTerm .= "%";
      break;
    case 'ends' :
      $searchTerm = "%" . $searchTerm;
      break;
    default :
      }

  return $searchTerm;
}


/**
 * Performs a search on the current RGD site.
 */
function makeRgdQueryLink($searchTerm) {
  return href(makeRgdQueryURL($searchTerm), $searchTerm);
}
  
function makeRgdQueryURL($searchTerm) {
  return '/tools/query/query.cgi?id='.$searchTerm;
}

function makeReferenceURL($refRGDID) {
  return '/tools/references/references_view.cgi?id='.$refRGDID;
}



function getExperimentsBySessionId($sessId) {
  $experimentCodes = fetchArrayForSelectField("select experiment.title, experiment.title as dummy from run, experiment where run.sess_id = $sessId and run.experiment_id = experiment.experiment_id");
  return implode(' / ', $experimentCodes);
}


function lookupDescriptions($delimitedString, $lookupArray) {
  // only need to look up stuff if there are some ids coming in
  if (isset($delimitedString)) {
    $exploded = explode('|', substr($delimitedString, 1, -1));
    foreach ($exploded as $code) {
      $translated[] = $lookupArray[$code];
    }
    return implode(' / ', $translated);
  }
}

function displayRunOtherData($delimitedString) {
  return lookupDescriptions($delimitedString, getRunOtherArray());
}

function displayDiagnosis($delimitedString) {
  return lookupDescriptions($delimitedString, getDiagnosisArray());
}

function displayLanguages($delimitedString) {
  return lookupDescriptions($delimitedString, getLanguagesArray());
}

function displayRaces($delimitedString) {
  return lookupDescriptions($delimitedString, getRaceArray());
}

function displayMedications($delimitedString) {
  return lookupDescriptions($delimitedString, getMedicationsArray());
}

function getHideLists() {
  if ('y' == getSessionVarOkEmpty('hideLists')) {
    return 'style="display: none;"';
  } 
  else {
    return '';
  }
}

function toggleHideLists() {
  if ('y' == getSessionVarOkEmpty('hideLists')) {
    setSessionVar('hideLists', 'n');
  } 
  else {
    setSessionVar('hideLists', 'y');
  }
  
}

function hashPassword($password) {
  // The 2nd parameter to sha1 was added in php5
  // $password = base64_encode(sha1($password, true));
  //
  // so, we're using 
  // a php4 workaround found at:
  // http://www.faqts.com/knowledge_base/view.phtml/aid/21821/fid/51
  // and also in the user notes for the sha1 function at the php site:
  // http://us3.php.net/sha1
  return base64_encode(pack("H*", sha1($password)));
}

function displaySubjects($items) {
  $table = newTable('ID', 'Full Name', 'DOB', 'Home Phone', 'Work Phone', 'Work Ext');
  $table->setAttributes('class="simple" width="100%"');
  foreach ($items as $item) {
    htmlEscapeValues($item);
    extract($item);
    $table->addRow(makeLink($SUBJECT_ID, 'subject', 'update', 'SUBJECT_ID='.$SUBJECT_ID), $FIRSTNAME.' '.$LASTNAME, $DOB, $HOMEPHONE, $WORKPHONE, $PHONEEXT);
  }
  return $table->toHtml();
}


function calculateLQ($item) {
  // From original Java Bean:
  // Applied this restraint on Dr. Binder 10/14/04 request, on 10/15/04.
  extract($item);
  if (isReallySet($HANDWRITING) && isReallySet($HANDDRAWING) && isReallySet($HANDTHROWING) &&
      isReallySet($HANDSCISSORS) && isReallySet($HANDTOOTHBRUSH) && isReallySet($HANDKNIFE) &&
      isReallySet($HANDSPOON) && isReallySet($HANDBROOM) && isReallySet($HANDSTRIKING) &&
      isReallySet($HANDOPENING) && isReallySet($HANDKICKING) && isReallySet($PREFERREDEYE)) {
    $lqScore = 0;
    // do the calculations
    $rScore = 0;
    $lScore = 0;
    // Loop through list of items.
    // Right Score: add 2 if value is 5, add 1 if value is 4 or 3.
    // Left Score:  add 2 if value is 1, add 1 if value is 2 or 3.
    $handArray = array('HANDWRITING','HANDDRAWING','HANDTHROWING','HANDSCISSORS',
                       'HANDTOOTHBRUSH','HANDKNIFE','HANDSPOON','HANDBROOM',
                       'HANDSTRIKING','HANDOPENING','HANDKICKING','PREFERREDEYE');
    foreach ($handArray as $handValue) {
      // echo "$handValue = " . $$handValue . "<br>\n";
      if ($$handValue == 5) {
         $rScore += 2;
      }
      else if ($$handValue == 4 || $$handValue == 3) {
         $rScore++;
      }
      if ($$handValue == 1) {
         $lScore += 2;
      }
      else if ($$handValue == 2 || $$handValue == 3) {
         $lScore++;
      }
    }
    if (($rScore + $lScore) != 0) {
      $lqScore = round(100 * ($rScore - $lScore) / ($rScore + $lScore));
    }
    return $lqScore;
  }
}

function calculateFLQ($item) {
  // From original Java Bean:
  // Applied this restraint on Dr. Binder 10/14/04 request, on 10/15/04.
  extract($item);
  if (isReallySet($HANDDOMMOTHER) && isReallySet($HANDDOMFATHER) && isReallySet($LEFTHANDBRSIS) && isReallySet($RIGHTHANDBRSIS) && isReallySet($LEFTHANDKIDS) && isReallySet($RIGHTHANDKIDS)) {
    $flqScore = 0;
    // do the calculations
    $frScore = 0;
    $flScore = 0;
    // compute $frScore
    $frScore = ($HANDDOMMOTHER   == 1) + 
               ($HANDDOMFATHER   == 1) +
               ($HANDDOMMGMOTHER == 1) +
               ($HANDDOMMGFATHER == 1) +
               ($HANDDOMPGMOTHER == 1) +
               ($HANDDOMPGFATHER == 1);
    if ($RIGHTHANDBRSIS != '') {
      $frScore += $RIGHTHANDBRSIS;
    }
    if ($RIGHTHANDKIDS != '') {
      $frScore += $RIGHTHANDKIDS;
    }

    // compute $flScore
    $flScore = ($HANDDOMMOTHER   == 2) + 
               ($HANDDOMFATHER   == 2) +
               ($HANDDOMMGMOTHER == 2) +
               ($HANDDOMMGFATHER == 2) +
               ($HANDDOMPGMOTHER == 2) +
               ($HANDDOMPGFATHER == 2);
    if ($LEFTHANDBRSIS != '') {
      $flScore += $LEFTHANDBRSIS;
    }
    if ($LEFTHANDKIDS != '') {
      $flScore += $LEFTHANDKIDS;
    }

    if (($frScore + $flScore) != 0) {
      $flqScore = round(100 * ($frScore - $flScore) / ($frScore + $flScore));
    }
    return $flqScore;
  }
  // if we never get into the above if statement, 
  // then that means we don't have enough info to compute a value,
  // so don't return anything, and the caller will be able to tell this situation
  // from the situation where the calculation actually comes out to 0 (zero)
}

function calculateBaronaFromSubAndSess($subject, $session) {
  $params['DOB'] = $subject['DOB'];
  $params['RACE'] = $subject['RACE'];
  $params['SEX'] = $subject['SEX'];
  $params['EDUCATION'] = $session['EDUCATION'];
  $params['BARONAOCCUPATION'] = $session['BARONAOCCUPATION'];
  
  $sessionDate = getArrayValueAtIndex($session, 'SESSDATE');
  if (isset($sessionDate)) {
    $dateArray = explode('/', $sessionDate);
    $year = $dateArray[2];
    return calculateBarona2IQ($params, $year);
  } 
}

function calculateBarona2IQ($item, $yearForAgeCalc = NULL) {
  // From original Java Bean:
  // computation only for race is white or black
  // Applied this restraint on Dr. Binder 10/14/04 request, on 10/15/04.
  // NOTE: Need to see if RACEID is array or singe value;
  
  if (!isset($yearForAgeCalc)) {
    $yearForAgeCalc = date("Y");
  }
  
  extract($item);
  if (isReallySet($DOB) && isReallySet($RACE) && isReallySet($SEX) &&
      isReallySet($EDUCATION) && isReallySet($BARONAOCCUPATION)) {
    $barona2Score = 0;
    // Need to extract year from $DOB
    // date in MM/DD/YYYY via Oracle date definition in dbFunctions.php getConnection
    $dateArray = explode('/', $DOB);
    $dobYear = $dateArray[2];
  
    // Need to extract out a race value where calculation requires
    // it to be 3 (Black) or 5 (White).  Making assumption that it 5
    // is found, then taking that value.
    $raceArray = array();
    $raceArray = explode("|", $RACE);
    $raceValue = '';
    foreach ($raceArray as $raceItemValue) {
      if ($raceItemValue == 3) {
        $raceValue = 3;
      }
      if ($raceItemValue == 5) {
        $raceValue = 5;
        break;
      }
    }
    
    if (isReallySet($dobYear) && ($raceValue == 5 || $raceValue == 3)) {
      // go ahead with the calculations...
      $barona2Score = 44.34;  // base start value
      $barona2Score += 2.73 * $EDUCATION;
      if ($raceValue == 5) {
        $barona2Score += 8.81;
      }
      if ($SEX == 'M') {
        $barona2Score += 2.2;
      }
      // default is to add zero
      switch ( $BARONAOCCUPATION ) {
        case 6: $barona2Score += 9.53; break;  // professional
        case 5: $barona2Score += 8.32; break;  // manager/clerk/prop
        case 4: $barona2Score += 6.36; break;  // craftsman
        case 3: $barona2Score += 6.58; break;  // unemployed
        case 2: $barona2Score += 3.68; break;  // operative
      }
      // approx age addition; checking for 4-digit years
      // assuming 4-digits since that is the Oracle format requested
      $approxAge = $yearForAgeCalc - $dobYear;
      if ($approxAge < 200) {
         $barona2Score += 0.16 * $approxAge;
      }
      else {
        // assuming max age of 99
        $tmpCurrentYear = substr($yearForAgeCalc, 2, 2) + 100;  // years from 1900
        $approxAge = $tmpCurrentYear - $dobYear;
        if ($approxAge > 100) {
           $approxAge -= 100;
        }
        $barona2Score += 0.16 * $approxAge;
      }
      return round($barona2Score);
    }
  }
}

function getUserID() {
  if (userLoggedIn()) {
    return getSessionVar('uid');
  }
  else {
    return '';
  }

}


function getUserFullName() {
  if (userLoggedIn()) {
    return getSessionVar('userFullName');
  }
  else {
    return '';
  }

}

/**
 * Check security level for access to a function. 
 */
function checkSecurityLevel($levelRequested) {
  if (userLoggedIn()) {
    $securityLevel = fetchField('select user_group from users where username = '.dbQuoteString( getSessionVar('uid')), 'LOGIN');
    if ( $securityLevel == 'admin' ) { 
      // Admin can do anything
      return true;
    } elseif ($securityLevel == 'manager' ) {
      // manager leve can do manager / pi / user  stuff. 
			if ( $levelRequested == 'manager' || $levelRequested == 'pi' || $levelRequested == 'user' ) {
        return true; 
      }
		} elseif ($securityLevel == 'pi') {
       // pi level can do / pi / user  stuff.
			if (  $levelRequested == 'pi' || $levelRequested == 'user' ) {
        return true; 
      }
		}
    // else security level must match
    return ($levelRequested == $securityLevel);
  }
  else {
    return false;
  }
}

function getSecurityLevel() {
  if (userLoggedIn()) {
    $securityLevel = fetchField('select user_group from users where username = '.dbQuoteString( getSessionVar('uid')), 'LOGIN');
    return $securityLevel;
  }
}

// top level of security, these folks can add users,
// change their security level, reset passwords
function userIsCurator() {
  return checkSecurityLevel("user");
}

// top level of security, these folks can add users,
// change their security level, reset passwords
function userIsAdmin() {
  return checkSecurityLevel("admin");
}

// Return true in the user logged in is an admin or PI
function userIsPIorAdmin() {
  return checkSecurityLevel("manager")|| checkSecurityLevel("pi") || checkSecurityLevel("admin") ;
}

// next level under admin, these folks can modify 
// conditions and experiments and lookup codes
function userIsPI() {
  return checkSecurityLevel("pi");
}

// next level under pi, these folks can do basic data
// entry of the subject records and children records
// under the subject records, but cannot update experiments
// or conditions or lookup codes
function userIsDataEntry() {
  return checkSecurityLevel("user") || userIsPI();
}

function userLoggedIn()
{
  $loggedInCookie = getCookieVar('userloggedin');
  if ($loggedInCookie == 1) {
    $uidSession = getSessionVarOkEmpty('uid');
    return (isset($uidSession));
  }
  else {
    return false;
  }
}


function displayFlag($flag) {
  return $flag == 1?'Y':'N';
}

function displayUserLevel($level) {
  if ($level == 1) {
    return 'Reg';
  }
  elseif ($level == 2) {
    return 'Client';
  }
  elseif ($level == 99) {
    return 'Admin';
  }
  else {
    return '';
  }
}

function getLoginForm() {
  $theForm = newForm('Login', 'POST', 'admin', 'login');

  $theForm->addText('username', 'Username', 12, 80, false);
  $theForm->addPassword('password', 'Password', 12, 50, false);
  return $theForm;
}

function getGenderArray() {
  return array (
  'M' => 'Male',
  'F' => 'Female');
}

function getYesNoArray() {
  return array (
  '1' => 'Yes',
  '2' => 'No');

}

function getGlassesContactsArray() {
  return array (
  '1' => 'Glasses',
  '2' => 'Contacts',
  '3' => 'Both');
}

function getNearFarSightedArray() {
  return array (
  '1' => 'Nearsighted',
  '2' => 'Farsighted');
}

function getSubjectActiveArray() {
  return array (
  '1' => 'Yes',
  '2' => 'No',
  '3' => 'Maybe');
}

function getMonths() {
  return array (
  '1' => 'Jan',
  '2' => 'Feb',
  '3' => 'Mar',
  '4' => 'Apr',
  '5' => 'May',
  '6' => 'Jun',
  '7' => 'Jul',
  '8' => 'Aug',
  '9' => 'Sep',
  '10' => 'Oct',
  '11' => 'Nov',
  '12' => 'Dec');
}

function getHeadacheTypeArray() {
  return array (
  '1' => 'Migraine',
  '2' => 'Cluster',
  '3' => 'Tension'
  );
}

function getSmokeTypeArray() {
  return array (
  '1' => 'Cigarette',
  '2' => 'Cigar',
  '3' => 'Pipe'
  );
}

function getKnowFromArray() {
  return array (
  '1' => 'Advertisement',
  '2' => 'Friend',
  '3' => 'Study recruiter',
  '4' => 'Other');
}

/**
 * Returns a match array for searchs as shown in this code : 
 *  <select name="match_type">
 *   <option value="equals" selected>Equals</option>
 *   <option value="contains">Contains</option>
 *   <option value="begins">Begins with</option>
 *   <option value="ends">Ends with</option>
 *  </select>
 */
function getSearchMatchType() { 
  return array (
    'equals' => 'Equals',
    'contains' => 'Contains',
    'begins' => 'Begins with',
    'ends' => 'Ends with' );
}

/** 
 * Escape a string to be used in the OverLib
 * 
 */
function escapeOverLib($string ) {
  $returnStr = str_replace('"', '', $string ); // fliter out double quotes 
  $returnStr = str_replace('\'', '\\\'', $returnStr );

  return $returnStr ; 
} 


/**
 * <select name="search_fields">

          <option value="symbols">Current Symbols</option>
          <option value="symbols_names">Current Symbols/Names</option>
          <option value="active_retired">Current and Withdrawn</option>
          <option value="all_with_aliases" selected>Current &amp; withdrawn &amp; 
          Aliases</option>
        </select>
 */
 function getGeneSearchFields() { 
  return array ( 
  'symbols' => 'Current Symbols',
  'symbols_name' => 'Current Symbols/Names',
  'active_retired' => 'Current and Withdrawn',
  'all_with aliases' => 'Current &amp; withdrawn &amp; Aliases' ) ; 
 }
 

        
        
function getYears() {
  $arrayOfYears = array();
  $currentDate = getDate();
  $currentYear = $currentDate['year'];
  for ($i = $currentYear; $i > 1991; $i-- ) {
    $arrayOfYears[$i] = $i;
  }
  return $arrayOfYears;
}

function getLanguagesArray() {
  // singleton... (even though we are stateless from one http
  // request to the next, it still helps
  // to do static/singleton stuff here since this method can be
  // called multiple times to process a single http request)
  static $theArray;
  if (isset($theArray)) {
    return $theArray;
  }
  else {
    $theArray = fetchArrayForSelectField('select code, descr from cd_languages order by descr');
    return $theArray;
  }
}

function getPiArray() {
  static $theArray;
  if (isset($theArray)) {
    return $theArray;
  }
  else {
    $theArray = fetchArrayForSelectField('select code, descr from cd_pis order by descr');
    return $theArray;
  }
}

function getKeyword1Array() {
  $theArray = fetchArrayForSelectField('select code1, descr from expkeyword1 order by descr');
  return $theArray;
}

function getKeyword2Array($keyword1) {
  $theArray = fetchArrayForSelectField('select code2, descr from expkeyword2 where code1 = '.$keyword1.' order by descr');
  return $theArray;
}

function getKeyword2Value($keyword1, $keyword2) {
  if (isset($keyword1) && isset($keyword2)) {
    return fetchField('select DESCR from expkeyword2 where code1='.$keyword1.' and code2='.$keyword2);
  }
}

function getKeyword3Array($keyword1, $keyword2) {
  $theArray = fetchArrayForSelectField('select code3, descr from expkeyword3 where code1 = '.$keyword1.' and code2 = '.$keyword2.' order by descr');
  return $theArray;
}

function getKeyword3Value($keyword1, $keyword2, $keyword3) {
  if (isset($keyword1) && isset($keyword2) && isset($keyword3)) {
    return fetchField('select DESCR from expkeyword3 where code1='.$keyword1.' and code2='.$keyword2.' and code3='.$keyword3);
  }
}

function getInstrumentArray() {
  static $theArray;
  if (isset($theArray)) {
    return $theArray;
  }
  else {
    $theArray = fetchArrayForSelectField('select code, descr from cd_instruments order by descr');
    return $theArray;
  }
}


function getHandednessArray() {
  return array (
  '1' => 'AL',
  '2' => 'PL',
  '3' => 'NP',
  '4' => 'PR',
  '5' => 'AR');
  
}

function getLobeArray() {
  return array (
  '1' => 'None',
  '2' => 'Part',
  '3' => 'All');
  
}

function getDominantHandArray() {
  return array (
  '1' => 'Right',
  '2' => 'Left');
}

function getDominantHandDbArray() {
  static $theArray;
  if (isset($theArray)) {
    return $theArray;
  }
  else {
    $theArray = fetchArrayForSelectField('select code, descr from cd_handdom order by descr');
    return $theArray;
  }
}

function getMedicationsArray() {
  static $theArray;
  if (isset($theArray)) {
    return $theArray;
  }
  else {
    $theArray = fetchArrayForSelectField('select code, descr from cd_medications order by descr');
    return $theArray;
  }
}

function getDiagnosisArray() {
  static $theArray;
  if (isset($theArray)) {
    return $theArray;
  }
  else {
    $theArray = fetchArrayForSelectField('select code, descr from cd_diagnosis order by descr');
    return $theArray;
  }
}

function getBaronaOccupationArray() {
  static $theArray;
  if (isset($theArray)) {
    return $theArray;
  }
  else {
    $theArray = fetchArrayForSelectField('select code, descr from cd_baronaoccupation order by descr');
    return $theArray;
  }
}



function getConditionArray() {
  static $theArray;
  if (isset($theArray)) {
    return $theArray;
  }
  else {
    $theArray = fetchArrayForSelectField('select condition_id, condcode from condition where pi_id ='.getSessionVar('pi').' order by condcode');
    return $theArray;
  }
}

function getConditionArrayByExperiment($experimentId) {
  static $theArray;
  if (isset($theArray)) {
    return $theArray;
  }
  else {
    $conditions = fetchField('select conditions from experiment where experiment_id ='.$experimentId);
    if (strlen($conditions) > 2) {
      $conditionIdArray = explode('|', substr($conditions, 1, -1)); 
      foreach($conditionIdArray as $conditionId) {
        $theArray[$conditionId] = fetchField('select condcode from condition where condition_id = '.$conditionId);
      }
    }
    return $theArray;
  }
}

function getRaceArray() {
  static $theArray;
  if (isset($theArray)) {
    return $theArray;
  }
  else {
    $theArray = fetchArrayForSelectField('select code, descr from cd_race order by descr');
    return $theArray;
  }
}

function getCoilsArray() {
  static $theArray;
  if (isset($theArray)) {
    return $theArray;
  }
  else {
    $theArray = fetchArrayForSelectField('select code, descr from cd_coils order by descr');
    return $theArray;
  }
}

function getRFCoilsArray() {
  static $theArray;
  if (isset($theArray)) {
    return $theArray;
  }
  else {
    $theArray = fetchArrayForSelectField('select code, descr from cd_rfcoils order by descr');
    return $theArray;
  }
}

function getHRRCArray() {
  static $theArray;
  if (isset($theArray)) {
    return $theArray;
  }
  else {
    $theArray = fetchArrayForSelectField('select hrrc_id, hrrc_descr from hrrc where piid='.getSessionVar('pi'));
    return $theArray;
  }
}

function getResponseArray() {
  static $theArray;
  if (isset($theArray)) {
    return $theArray;
  }
  else {
    $theArray = fetchArrayForSelectField('select code, descr from cd_response order by descr');
    return $theArray;
  }
}

function getActiveArray() { 
  return array( 'Y' => "Active", 'N' => "Inactive" ) ; 
}

/** 
 * Return the next value for a particular $rowname from $table 
 */
function getNextDBKey (  $table,  $dbconnection = NULL ) {
  // $keyArray = fetchRecord( "select max(". $rowName . ") +1 as value from " . $table, $dbconnection ) ; 
  $keyArray = fetchRecord ( "select " . $table . "_seq.nextval as value from dual", $dbconnection ) ; 
  return $keyArray['VALUE']; 
}


/**
 * The letter l (lowercase L) and the number 1
 * have been removed, as they can be mistaken
 * for each other.
 */

function createRandomPassword($size = 7) {

    if ( $size < 2 ) { $size = 7; } 
    $chars = "abcdefghijkmnopqrstuvwxyz023456789";
    srand((double)microtime()*1000000);
    $i = 0;
    $pass = '' ;

    while ($i <= $size -1 ) {
        $num = rand() % 33;
        $tmp = substr($chars, $num, 1);
        $pass = $pass . $tmp;
        $i++;
    }

    return $pass;

}

function getSecurityRoleArray() {
  static $returnArray ;
  if (isset($returnArray)) {
    return $returnArray;
  }
  else {
    // $theArray = fetchArrayForSelectField('select code, descr from cd_roles order by descr');
    $theArray = fetchRecords('select unique ( user_group )  from users order by user_group', 'LOGIN');
    foreach ( $theArray as $key => $role) { 
     //  dump ($role['USER_GROUP'] ) ; 
      $returnArray[$role['USER_GROUP']] = $role['USER_GROUP']; 
    } 
    // dump ( $returnArray); 
    return $returnArray;
  }
  
}

function getScanTypeArray() {
  static $theArray;
  if (isset($theArray)) {
    return $theArray;
  }
  else {
    $theArray = fetchArrayForSelectField('select code, descr from cd_scantypes order by descr');
    return $theArray;
  }
}

function getAnatScanTypeArray() {
  static $theArray;
  if (isset($theArray)) {
    return $theArray;
  }
  else {
    $theArray = fetchArrayForSelectField('select code, descr from cd_anatscantypes order by descr');
    return $theArray;
  }
}

function getAnatScanTypesBySession($sessionId) {
  $scantypes = fetchArrayForSelectField('select code, descr from cd_anatscantypes, anatscan where anatscan.type = cd_anatscantypes.code and anatscan.sess_id = '.$sessionId);
  return implode(' / ', $scantypes);
}

function getRunOtherArray() {
  static $theArray;
  if (isset($theArray)) {
    return $theArray;
  }
  else {
    $theArray = fetchArrayForSelectField('select code, descr from cd_exprother order by descr');
    return $theArray;
  }
}

function getRunVariablesArray() {
  static $theArray;
  if (isset($theArray)) {
    return $theArray;
  }
  else {
    $theArray = fetchArrayForSelectField('select code, descr from cd_runvariables order by descr');
    return $theArray;
  }
}

function getExperimentArray() {
  static $theArray;
  if (isset($theArray)) {
    return $theArray;
  }
  else {
    $theArray = fetchArrayForSelectField('select experiment_id, code||\'  (\'||title||\')\' from experiment where pi_id = '.getSessionVar('pi').' order by code');
    return $theArray;
  }
}

function getGCRCArray() {
  static $theArray;
  if (isset($theArray)) {
    return $theArray;
  }
  else {
    $theArray = fetchArrayForSelectField('select gcrcprotocol_id, descr from gcrcprotocol where piid='.getSessionVar('pi'));
    return $theArray;
  }
}

function getGrantArray() {
  static $theArray;
  if (isset($theArray)) {
    return $theArray;
  }
  else {
    $theArray = fetchArrayForSelectField('select fmrigrant_id, descr from fmrigrant where piid='.getSessionVar('pi'));
    return $theArray;
  }
}

function getSessionOutcomeArray() {
  return array (
    '1'=> 'Complete',
    '2'=> 'Incomplete'
    );
}



function getAnatScanPlaneArray() {
  // this was hardcoded in old application:
  return array (
    '1'=> 'Axial',
    '2'=> 'Coronal',
    '3'=> 'Sagittal'
    );
}

function getEyeArray() {
  // this was hardcoded in old application:
  return array (
    '1'=> 'Right',
    '2'=> 'Left'
    );
}

function getOpticsArray() {
  static $theArray;
  if (isset($theArray)) {
    return $theArray;
  }
  else {
    $theArray = fetchArrayForSelectField('select code, descr from cd_optics order by descr');
    return $theArray;
  }
}

function getProjectorArray() {
  static $theArray;
  if (isset($theArray)) {
    return $theArray;
  }
  else {
    $theArray = fetchArrayForSelectField('select code, descr from cd_projectors order by descr');
    return $theArray;
  }
}





function getDesignArray() {
  // this was hardcoded in old application:
  return array (
    '1'=> 'Block',
    '2'=> 'Event-related',
    '3'=> 'Mixed',
    '4'=> 'Other (specify)'
    );
}


function getPlaneArray() {
  // this was hardcoded in old application:
  return array (
    '1'=> 'Axial',
    '2'=> 'Coronal',
    '3'=> 'Sagittal'
    );
}

function getReportFormatArray() {
  return array (
  '1'=> 'HTML',
  '2'=> 'Excel/CSV'
  );
}

function getScannerArray() {
  // this was hardcoded in old application:
  return array (
    '1'=> '1.5T keck',
    '2'=> '3T bruker',
    '3'=> 'GE 3T FMLH',
    '4'=> 'GE 3T MCW'
    );
}

function getStateArray()
{
  return array (
    ""=> "",
    "AL"=> "AL - Alabama",
    "AK"=> "AK - Alaska",
    "AS"=> "AS - American Samoa",
    "AZ"=> "AZ - Arizona",
    "AR"=> "AR - Arkansas",
    "CA"=> "CA - California",
    "CO"=> "CO - Colorado",
    "CT"=> "CT - Connecticut",
    "DE"=> "DE - Delaware",
    "DC"=> "DC - District of Columbia",
    "FM"=> "FM - Federated States of Micronesia",
    "FL"=> "FL - Florida",
    "GA"=> "GA - Georgia",
    "GU"=> "GU - Guam",
    "HI"=> "HI - Hawaii",
    "ID"=> "ID - Idaho",
    "IL"=> "IL - Illinois",
    "IN"=> "IN - Indiana",
    "IA"=> "IA - Iowa",
    "KS"=> "KS - Kansas",
    "KY"=> "KY - Kentucky",
    "LA"=> "LA - Louisiana",
    "ME"=> "ME - Maine",
    "MH"=> "MH - Marshall Islands",
    "MD"=> "MD - Maryland",
    "MA"=> "MA - Massachusetts",
    "MI"=> "MI - Michigan",
    "MN"=> "MN - Minnesota",
    "MS"=> "MS - Mississippi",
    "MO"=> "MO - Missouri",
    "MT"=> "MT - Montana",
    "NE"=> "NE - Nebraska",
    "NV"=> "NV - Nevada",
    "NH"=> "NH - New Hampshire",
    "NJ"=> "NJ - New Jersey",
    "NM"=> "NM - New Mexico",
    "NY"=> "NY - New York",
    "NC"=> "NC - North Carolina",
    "ND"=> "ND - North Dakota",
    "MP"=> "MP - Northern Mariana Islands",
    "OH"=> "OH - Ohio",
    "OK"=> "OK - Oklahoma",
    "OR"=> "OR - Oregon",
    "PW"=> "PW - Palau",
    "PA"=> "PA - Pennsylvania",
    "PR"=> "PR - Puerto Rico",
    "RI"=> "RI - Rhode Island",
    "SC"=> "SC - South Carolina",
    "SD"=> "SD - South Dakota",
    "TN"=> "TN - Tennessee",
    "TX"=> "TX - Texas",
    "UT"=> "UT - Utah",
    "VT"=> "VT - Vermont",
    "VI"=> "VI - Virgin Islands",
    "VA"=> "VA - Virginia",
    "WA"=> "WA - Washington",
    "WV"=> "WV - West Virginia",
    "WI"=> "WI - Wisconsin",
    "WY"=> "WY - Wyoming",
    "AB"=> "AB - Alberta",
    "BC"=> "BC - British Columbia",
    "MB"=> "MB - Manitoba",
    "NB"=> "NB - New Brunswick",
    "NF"=> "NF - Newfoundland",
    "NT"=> "NT - Northwest Territory",
    "NS"=> "NS - Nova Scotia",
    "NU"=> "NU - Nunavut Territory",
    "ON"=> "ON - Ontario",
    "PE"=> "PE - Prince Edward Island",
    "QC"=> "QC - Quebec",
    "SK"=> "SK - Saskatchewan",
    "YT"=> "YT - Yukon Territory"
  );
}

function getCountryArray()
{
  return array (
    "US" => "United States of America",
    "AF" => "Afghanistan",
    "AL" => "Albania",
    "DZ" => "Algeria",
    "AS" => "American Samoa",
    "AD" => "Andorra",
    "AO" => "Angola",
    "AI" => "Anguilla",
    "AQ" => "Antarctica",
    "AG" => "Antigua and Barbuda",
    "AR" => "Argentina",
    "AM" => "Armenia",
    "AW" => "Aruba",
    "AU" => "Australia",
    "AT" => "Austria",
    "AZ" => "Azerbaijan",
    "BS" => "Bahamas",
    "BH" => "Bahrain",
    "BD" => "Bangladesh",
    "BB" => "Barbados",
    "BY" => "Belarus",
    "BE" => "Belgium",
    "BZ" => "Belize",
    "BJ" => "Benin",
    "BM" => "Bermuda",
    "BT" => "Bhutan",
    "BO" => "Bolivia",
    "BA" => "Bosnia and Herzegovina",
    "BW" => "Botswana",
    "BV" => "Bouvet Island",
    "BR" => "Brazil",
    "IO" => "British Indian Ocean Territory",
    "BN" => "Brunei Darussalam",
    "BG" => "Bulgaria",
    "BF" => "Burkina Faso",
    "BI" => "Burundi",
    "KH" => "Cambodia",
    "CM" => "Cameroon",
    "CA" => "Canada",
    "CV" => "Cape Verde",
    "KY" => "Cayman Islands",
    "CF" => "Central African Republic",
    "TD" => "Chad",
    "CL" => "Chile",
    "CN" => "China",
    "CX" => "Christmas Island",
    "CC" => "Cocos (Keeling) Islands",
    "CO" => "Colombia",
    "KM" => "Comoros",
    "CG" => "Congo",
    "CK" => "Cook Islands",
    "CR" => "Costa Rica",
    "CI" => "Cote D'Ivoire",
    "HR" => "Croatia (Hrvatska)",
    "CU" => "Cuba",
    "CY" => "Cyprus",
    "CZ" => "Czech Republic",
    "DK" => "Denmark",
    "DJ" => "Djibouti",
    "DM" => "Dominica",
    "DO" => "Dominican Republic",
    "TP" => "East Timor",
    "EC" => "Ecuador",
    "EG" => "Egypt",
    "SV" => "El Salvador",
    "GQ" => "Equatorial Guinea",
    "ER" => "Eritrea",
    "EE" => "Estonia",
    "ET" => "Ethiopia",
    "FK" => "Falkland Islands (Malvinas)",
    "FO" => "Faroe Islands",
    "FJ" => "Fiji",
    "FI" => "Finland",
    "FR" => "France",
    "FX" => "France, Metropolitan",
    "GF" => "French Guiana",
    "PF" => "French Polynesia",
    "TF" => "French Southern Territories",
    "GA" => "Gabon",
    "GM" => "Gambia",
    "GE" => "Georgia",
    "DE" => "Germany",
    "GH" => "Ghana",
    "GI" => "Gibraltar",
    "GR" => "Greece",
    "GL" => "Greenland",
    "GD" => "Grenada",
    "GP" => "Guadeloupe",
    "GU" => "Guam",
    "GT" => "Guatemala",
    "GN" => "Guinea",
    "GW" => "Guinea-Bissau",
    "GY" => "Guyana",
    "HT" => "Haiti",
    "HM" => "Heard and Mc Donald Islands",
    "HN" => "Honduras",
    "HK" => "Hong Kong",
    "HU" => "Hungary",
    "IS" => "Iceland",
    "IN" => "India",
    "ID" => "Indonesia",
    "IR" => "Iran (Islamic Republic of)",
    "IQ" => "Iraq",
    "IE" => "Ireland",
    "IL" => "Israel",
    "IT" => "Italy",
    "JM" => "Jamaica",
    "JP" => "Japan",
    "JO" => "Jordan",
    "KZ" => "Kazakhstan",
    "KE" => "Kenya",
    "KI" => "Kiribati",
    "KP" => "Korea, Democratic People's Republic of",
    "KR" => "Korea, Republic of",
    "KO" => "Kosovo",
    "KW" => "Kuwait",
    "KG" => "Kyrgyzstan",
    "LA" => "Lao People's Democratic Republic",
    "LV" => "Latvia",
    "LB" => "Lebanon",
    "LS" => "Lesotho",
    "LR" => "Liberia",
    "LY" => "Libyan Arab Jamahiriya",
    "LI" => "Liechtenstein",
    "LT" => "Lithuania",
    "LU" => "Luxembourg",
    "MO" => "Macau",
    "MK" => "Macedonia, Former Yugoslav Republic of",
    "MG" => "Madagascar",
    "MW" => "Malawi",
    "MY" => "Malaysia",
    "MV" => "Maldives",
    "ML" => "Mali",
    "MT" => "Malta",
    "MH" => "Marshall Islands",
    "MQ" => "Martinique",
    "MR" => "Mauritania",
    "MU" => "Mauritius",
    "YT" => "Mayotte",
    "MX" => "Mexico",
    "FM" => "Micronesia, Federated States of",
    "MD" => "Moldova, Republic of",
    "MC" => "Monaco",
    "MN" => "Mongolia",
    "MS" => "Montserrat",
    "MA" => "Morocco",
    "MZ" => "Mozambique",
    "MM" => "Myanmar",
    "NA" => "Namibia",
    "NR" => "Nauru",
    "NP" => "Nepal",
    "NL" => "Netherlands",
    "AN" => "Netherlands Antilles",
    "NC" => "New Caledonia",
    "NZ" => "New Zealand",
    "NI" => "Nicaragua",
    "NE" => "Niger",
    "NG" => "Nigeria",
    "NU" => "Niue",
    "NF" => "Norfolk Island",
    "MP" => "Northern Mariana Islands",
    "NO" => "Norway",
    "OM" => "Oman",
    "PK" => "Pakistan",
    "PW" => "Palau",
    "PA" => "Panama",
    "PG" => "Papua New Guinea",
    "PY" => "Paraguay",
    "PE" => "Peru",
    "PH" => "Philippines",
    "PN" => "Pitcairn",
    "PL" => "Poland",
    "PT" => "Portugal",
    "PR" => "Puerto Rico",
    "QA" => "Qatar",
    "RE" => "Reunion",
    "RO" => "Romania",
    "RU" => "Russian Federation",
    "RW" => "Rwanda",
    "KN" => "Saint Kitts and Nevis",
    "LC" => "Saint Lucia",
    "VC" => "Saint Vincent and the Grenadines",
    "WS" => "Samoa",
    "SM" => "San Marino",
    "ST" => "Sao Tome and Principe",
    "SA" => "Saudi Arabia",
    "SN" => "Senegal",
    "CS" => "Serbia and Montenegro",
    "SC" => "Seychelles",
    "SL" => "Sierra Leone",
    "SG" => "Singapore",
    "SK" => "Slovakia (Slovak Republic)",
    "SI" => "Slovenia",
    "SB" => "Solomon Islands",
    "SO" => "Somalia",
    "ZA" => "South Africa",
    "ES" => "Spain",
    "LK" => "Sri Lanka",
    "SH" => "St. Helena",
    "PM" => "St. Pierre and Miquelon",
    "SD" => "Sudan",
    "SR" => "Suriname",
    "SJ" => "Svalbard and Jan Mayen Islands",
    "SZ" => "Swaziland",
    "SE" => "Sweden",
    "CH" => "Switzerland",
    "SY" => "Syrian Arab Republic",
    "TW" => "Taiwan",
    "TJ" => "Tajikistan",
    "TZ" => "Tanzania, United Republic of",
    "TH" => "Thailand",
    "TG" => "Togo",
    "TK" => "Tokelau",
    "TO" => "Tonga",
    "TT" => "Trinidad and Tobago",
    "TN" => "Tunisia",
    "TR" => "Turkey",
    "TM" => "Turkmenistan",
    "TC" => "Turks and Caicos Islands",
    "TV" => "Tuvalu",
    "UG" => "Uganda",
    "UA" => "Ukraine",
    "AE" => "United Arab Emirates",
    "GB" => "United Kingdom",
    "UY" => "Uruguay",
    "UZ" => "Uzbekistan",
    "VU" => "Vanuatu",
    "VA" => "Vatican City State (Holy See)",
    "VE" => "Venezuela",
    "VN" => "Vietnam",
    "VG" => "Virgin Islands (British)",
    "VI" => "Virgin Islands (U.S.)",
    "WA" => "Wales",
    "WF" => "Wallis And Futuna Islands",
    "EH" => "Western Sahara",
    "YE" => "Yemen",
    "ZR" => "Zaire",
    "ZM" => "Zambia",
    "ZW" => "Zimbabwe");
}


/**
 * Create an HTML link to colored indicator image based on status of object 
 */
function makeObjectStatusLink($status) {
  switch ($status) {
    case 'ACTIVE' :
      return '<img src="icons/flag_green.png" title="Active" alt="ACTIVE">';
      break;
    case 'RETIRED' :
      return '<img src="icons/bomb.png" title="Retired" alt="RETIRED">';
      break;
    case 'WITHDRAWN' :
      return '<img src="icons/error.png" title="Withdrawn" alt="WITHDRAWN">';
      break;
    default :
      return $status;
  }
}
/**
 * Create an HTML link to colored indicator image based on status of a term  1 or 0 VOC_TERM.IS_OBSOLETE
 */
function makeTermStatusLink($status) {
  switch ($status) {
    case '0' :
      return '<img src="icons/flag_green.png" title="Active" alt="ACTIVE">';
      break;
    case '1' :
      return '<img src="icons/error.png" title="Obsolete" alt="Obsolete">';
      break;
    default :
      return $status;
  }
}

/** 
 * Return the species name given the species Key 
 * 
 */
function getSpeciesName( $speciesTypeKey ) {
   
  switch ($speciesTypeKey) {
    case '1' :
      return 'Human';
      break;
    case '3' :
      return 'Rat';
      break;
    case '2' :
      return 'Mouse';
      break;
    default :
      return "Undefined Species";
  }
}
function getSpeciesNameAndAll( $speciesTypeKey,$includefor=false ) {
   $toReturn='';
   if ($includefor)
   {
        $toReturn='for ';
   }
  switch ($speciesTypeKey) {
    case '1' :
      return $toReturn.'Human';
      break;
    case '3' :
      return $toReturn.'Rat';
      break;
    case '2' :
      return $toReturn.'Mouse';
      break;
    case '1,2,3':
      return '';
      break;
    default :
      return $toReturn."Undefined Species";
  }
}
/**
 * Returns array of species ready for drop Down List
 */
function getSpeciesArrayForDropDown() {
   
  return ( array ( 1 => 'Human', 2=> 'Mouse', 3=> 'Rat' ) ); 
}
function getSpeciesArrayForDropDownAndAll() {
   
  return ( array ( 1 => 'Human', 2=> 'Mouse', 3=> 'Rat', '1,2,3'=>'All' ) ); 
}


/**
 * Takes Species name or SpeciesID from database and returns HTML link to image
 */
function makeSpeciesLink($speciesTypeKey) {
  switch ($speciesTypeKey) {
    case '1' :
    case 'Human' :
      return '<img src="images/logo-human.gif" alt="Human" title="Human">';
      break;
    case '3' :
    case 'Rat' :
      return '<img src="images/logo-rat.gif" alt="Rat" title="Rat">';
      break;
    case '2' :
    case 'Mouse' :
      return '<img src="images/logo-mouse.gif" alt="Mouse" title="Mouse">';
      break;
    default :
      return "Undefined Species";
  }
}

/**
 * Return an array of the object results with the key being the database column names 
 * 
 */
function getObjectInfoByRGID($rgdID) { 
  $returnArray = array ();
  $sql = 'select o.OBJECT_NAME from rgd_ids r, rgd_objects o where r.rgd_id = ' . $rgdID . ' and r.object_key = o.object_key';
  $resultType = fetchRecord($sql);

  $returnName = '';
  switch ($resultType['OBJECT_NAME']) {

    case 'GENES' :
      $result = fetchRecord('select g.*,  r.OBJECT_KEY, r.species_type_key  from genes g, rgd_ids r where  g.rgd_id = r.rgd_id and g.rgd_id =  ' . $rgdID);
      $returnArray['SYMBOL'] = $result['GENE_SYMBOL'];
      $returnArray['SPECIES'] = $result['SPECIES_TYPE_KEY'];
      $returnArray['NAME'] = $result['FULL_NAME'];
      $returnArray['OBJECT_KEY'] = $result['OBJECT_KEY'];
      $returnArray['GENE_KEY'] = $result['GENE_KEY'];
      $returnArray['GENE_TYPE'] = $result['GENE_TYPE_LC'];
      $returnArray['ID'] = $result['RGD_ID'];
      $returnArray['TYPE'] = 'G';
      break;

    case 'QTLS' :
      $result = fetchRecord('select q.*,  r.OBJECT_KEY, r.SPECIES_TYPE_KEY from qtls q , rgd_ids r where  q.rgd_id = r.rgd_id and q.rgd_id = ' . $rgdID);
      $returnArray['SYMBOL'] = $result['QTL_SYMBOL'];
      $returnArray['SPECIES'] = $result['SPECIES_TYPE_KEY'];
      $returnArray['NAME'] = $result['QTL_NAME']; // used when creating association
      $returnArray['OBJECT_KEY'] = $result['OBJECT_KEY'];
      $returnArray['ID'] = $result['RGD_ID'];
      $returnArray['QTL_KEY'] = $result['QTL_KEY'];
      $returnArray['TYPE'] = 'Q';
      break;

    case 'STRAINS' :
      $result = fetchRecord('select s.*,  r.OBJECT_KEY, r.SPECIES_TYPE_KEY from strains s , rgd_ids r where  s.rgd_id = r.rgd_id and  s.rgd_id = ' . $rgdID);
      $returnArray['SYMBOL'] = $result['STRAIN_SYMBOL'];
      $returnArray['SPECIES'] = $result['SPECIES_TYPE_KEY'];
      $returnArray['NAME'] = $result['FULL_NAME'];
      $returnArray['OBJECT_KEY'] = $result['OBJECT_KEY'];
      $returnArray['STRAIN_KEY'] = $result['STRAIN_KEY'];
      $returnArray['ID'] = $result['RGD_ID'];
      $returnArray['TYPE'] = 'S';
      break;

    case 'HOMOLOGS' :
      $result = fetchRecord('select g.*,  r.OBJECT_KEY, r.species_type_key  from genes g, rgd_ids r where  g.rgd_id = r.rgd_id and g.rgd_id =  ' . $rgdID);
      $returnArray['SYMBOL'] = $result['GENE_SYMBOL'];
      $returnArray['SPECIES'] = $result['SPECIES_TYPE_KEY'];
      $returnArray['NAME'] = $result['FULL_NAME'];
      $returnArray['OBJECT_KEY'] = $result['OBJECT_KEY'];
      $returnArray['GENE_KEY'] = $result['GENE_KEY'];
      $returnArray['GENE_TYPE'] = $result['GENE_TYPE_LC'];
      $returnArray['ID'] = $result['RGD_ID'];
      $returnArray['TYPE'] = 'G';
      break;

    case 'SSLPS' :
      $result = fetchRecord('select s.*, r.OBJECT_KEY, r.SPECIES_TYPE_KEY from sslps s, rgd_ids r where   s.rgd_id=r.rgd_id and s.rgd_id='. $rgdID);
      $returnArray['SYMBOL'] = $result['RGD_NAME'];
      $returnArray['SPECIES'] = $result['SPECIES_TYPE_KEY'];
      $returnArray['NAME'] = $result['RGD_NAME'];
      $returnArray['OBJECT_KEY'] = $result['OBJECT_KEY'];
      $returnArray['ID'] = $result['RGD_ID'];
      $returnArray['SSLP_KEY'] = $result['SSLP_KEY'];
      $returnArray['TYPE'] = 'SS';
      break;

    case 'REFERENCES' :
      $result = fetchRecord('select ref.*, r.OBJECT_KEY from references ref, rgd_ids r where   ref.rgd_id = r.rgd_id and  ref.rgd_id=' . $rgdID);
      $returnArray['OBJECT_KEY'] = $result['OBJECT_KEY'];
      $returnArray['TITLE'] = $result['TITLE'];
      $returnArray['ID'] = $result['RGD_ID'];
      $returnArray['REF_KEY'] = $result['REF_KEY'];
      $returnArray['TYPE'] = 'R';
      break;
    default :
      }

  return $returnArray;
}
/**
 * Returns an HMTL link back to the wiki for Help on a topic and subtopic . 
 */
function createHelpLinkCW( $topic, $subtopic) {
  $wikiURL = "http://wiki.hmgc.mcw.edu/wiki/index.php/CurationWeb/";
  return "<a href='" . $wikiURL . $topic . "/". $subtopic . "' title='Help for " . $subtopic . "'><img src='icons/help.png' border=0></a>"; 
}

/**
 * Returns an array of timestamps consisting of the $fromdate, the first days of all months between the $fromDate and the $toDate, and the $toDate
 * All timestamps are at midnight, except for the last one, which is at 11:59:59 pm.
 */
function getStartOfMonthsByDates($fromDate,$toDate)
{
    $switchNeeded=false;
    $date1=array();
    $date1['Month']=substr($fromDate,0,2);
    $date1['Day']=substr($fromDate,3,2);
    $date1['Year']=substr($fromDate,6,4);
    $date2=array();
    $date2['Month']=substr($toDate,0,2);
    $date2['Day']=substr($toDate,3,2);
    $date2['Year']=substr($toDate,6,4);
    if ($date2['Year']<$date1['Year'])
    {
        $switchNeeded=true;
    }
    else if ($date2['Year']==$date1['Year'])
    {
        if ($date2['Month']<$date1['Month'])
        {
            $switchNeeded=true;
        }
        else if ($date2['Month']==$date1['Month'])
        {
            if ($date2['Day']<$date1['Day'])
            {
                $switchNeeded=true;
            }
        }
    }
    //$firstDate=array();
    //$secondDate=array();
    if ($switchNeeded)
    {
        $firstDate=$date2;
        $secondDate=$date1;
    }
    else
    {
        $firstDate=$date1;
        $secondDate=$date2;
    }
    //only 12-14-1901 to 1-17-2038
    if ($firstDate['Year']>2038)
    {
        $firstDate['Day']=17;
        $firstDate['Month']=1;
        $firstDate['Year']=2038;
    }
    else if ($secondDate['Year']==2038)
    {
        $secondDate['Month']==1;
        if ($secondDate['Day']>17)
        {
            $secondDate['Day']=17;
        }
    }
    if ($secondDate['Year']>2038)
    {
        $secondDate['Day']=17;
        $secondDate['Month']=1;
        $secondDate['Year']=2038;
    }
    else if ($secondDate['Year']==2038)
    {
        $secondDate['Month']==1;
        if ($secondDate['Day']>17)
        {
            $firstDate['Day']=17;
        }
    }
    if ($firstDate['Year']<1901)
    {
        $firstDate['Day']=14;
        $firstDate['Month']=12;
        $firstDate['Year']=1901;
    }
    else if ($secondDate['Year']==1901)
    {
        $secondDate['Month']==12;
        if ($secondDate['Day']<14)
        {
            $secondDate['Day']=14;
        }
    }
    if ($secondDate['Year']<1901)
    {
        $secondDate['Day']=14;
        $secondDate['Month']=12;
        $secondDate['Year']=1901;
    }
    else if ($secondDate['Year']==1901)
    {
        $secondDate['Month']==12;
        if ($secondDate['Day']<14)
        {
            $firstDate['Day']=14;
        }
    }
    //$firstDate and $secondDate are now in order chronologically
    $month=$secondDate['Month'];
    $year=$secondDate['Year'];
    $day=$secondDate['Day'];
    $toReturn=array();
    $index=0;
    $toReturn[0]=mktime(23,59,59,$month,$day,$year);
    $finalStamp=mktime(0,0,0,$firstDate['Month'],$firstDate['Day'],$firstDate['Year']);
    //$day=1;
    while (true)
    {
        if ($day!=1||$index==0)
        {
            $day=1;
        }
        else
        {
            $month--;
            if ($month==0)
            {
                $month=12;
                $year--;
            }
        }
        $index++;
        $stamp=mktime(0,0,0,$month,$day,$year);
        if ($stamp<=$finalStamp)
        {
            break;
        }
        $toReturn[$index]=$stamp;
        //if ()
        //$stamp=last_day($month,$year);
        //$date=
    }
    $toReturn[$index]=$finalStamp;
    return array_reverse($toReturn);
}
/**Get all of the current options (right now, only color)
 * Returns the color as a string
 * Hex colors have # in front of them
 */
function setGraphDefaults()
{
    $color=getSessionVarOKEmpty('GraphColor');
    if (!isset($color))
    {
        //default to blue
        $color='blue';
    }
    return $color;
}

/**
 * Returns one year ago plus one day
 * Useful for defaulting coolDate
 */
 function getLastYear()
 {
    if ((date('n')>=3&&date('Y')%4!=0)||(date('n')<3&&date('Y')-1%4!=0))
    {
        return date('m/d/Y',gettimeofday(true)-31536000+86400);
    }
    else
    {
        return date('m/d/Y',gettimeofday(true)-31536000);
    }
 }
 function getAnnotationArrayForDropDown()
{
    return array('G%'=>'GO','D%'=>'DO','PW%'=>'PW','MP%'=>'MP');
}
function getXDBArrayForDropDown()
{
    $numIDs=fetchRecords("SELECT r.XDB_KEY,r.XDB_NAME from RGD_XDB r");
    $toReturn=array();
    foreach ($numIDs as $numID)
    {
        $toReturn[$numID['XDB_KEY']]=$numID['XDB_NAME'];
    }
    return $toReturn;
}
?>
