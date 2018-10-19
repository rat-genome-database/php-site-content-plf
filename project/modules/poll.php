<?php

//Renders the page within the site
function poll_renderWithHeader() {
    setTemplate('default');

    $html = "";
$tmp = <<< HTML
    <br>
    <table align="center">
    <tr>
        <td>
HTML;
$html = $html . $tmp;
    $html = $html . renderPoll(400);

$tmp = <<< HTML
        </td>
    </tr>
    </table>
HTML;
$html = $html . $tmp;

return $html;
}

//renders the page for the inline div on home page
function poll_render() {
    setTemplate('poll');
    return renderPoll(220, "Submit", "pollSubmit(this.form)");
}

//renders the poll for the modal dialog
function poll_renderDialog() {
    setTemplate('pollDialog');
    return renderPoll(470, "Submit and Continue >>>", "pollSubmit(this.form)");
	
}

//Updates the results database with a poll response.
//Form parameters are passed via the pollQuestion array where the option id is used as a hash key
//Comments are passed via the convention comment_<poll option id>
function submitResults($pollID) {
    for($j=0; $j < count($_REQUEST['pollQuestion']); $j++) {
        $comment="";
        if (isset($_REQUEST["comment_". $_REQUEST['pollQuestion'][$j]])) {
            $comment = $_REQUEST["comment_". $_REQUEST['pollQuestion'][$j]];
            $comment = str_replace("'", "''", $comment);
        }
        $query="INSERT into poll_result (RESULT_ID, POLL_ID, IP_ADDRESS, OPTION_ID, RESULT_DATE, RESULT_COMMENT) ";                                                       //H:i:s                                   //2004-12-01 20:37:22'
        $query = $query . "Values (poll_result_seq.nextval,". $pollID . ",'" . $_SERVER['REMOTE_ADDR'] . "'," . $_REQUEST['pollQuestion'][$j] . ",to_date('" . date('Y-m-d') . "','YYYY-MM-DD'),'". $comment . "')";
        executeUpdate($query, "POLL");
   }
}


function renderPoll($pollWidth, $submitText, $javascriptSubmitFunction="") {

    if (isset($_REQUEST['skip'])) {    
        setTemplate('poll');
        $datetimeStamp = date('Y-m-d H:i:s');
        $myFile = "/user/apache/poll/skip-" . date('Ymd');
        $fh = fopen($myFile, 'a') or die("can't open file");
        fwrite($fh, $datetimeStamp . "," . $_SERVER['REMOTE_ADDR'] . "\n");
        fclose($fh);
    }

    //get the server hostname.  Paths are fully qualified since staff may paste the poll in email
    $serverRoot="";
    if (isset($_SERVER['HTTP_HOST'])) {
        $serverRoot = "http://" . $_SERVER['HTTP_HOST'];
    }
    $pollID = getRequestVarNum("pollID", 21);

    //Name of our cookie
    $cookie = "Voted". $pollID;
    //set if the user has already voted
    
    $alreadyVoted = false;    
    
    if (isset($_COOKIE[$cookie])) {
		    if ($_COOKIE["Voted" . $pollID] == "Voted") {
			    $alreadyVoted=true;	    
		    }
    } 
    
    //set if it is a results request
    $isResultRequest = getRequestVarString("viewResults",false);
    //set if it is a new submition
    $isNewSubmition = (isset($_REQUEST['pollQuestion']) || isset($_REQUEST['other']));
    $optionResults = null;

    if ($isNewSubmition || $alreadyVoted || $isResultRequest ) {
        if($isNewSubmition && !$alreadyVoted){
            submitResults($pollID);
            $month = 31104000 + time();
            setcookie("Voted".$pollID, "Voted", $month, "/");
        }
    }

    $displayResults=false;
    $stats = fetchRecords("select * from poll_result where poll_id=". $pollID . " order by option_id", "POLL");
    $totalCount=count($stats);

    if ($isNewSubmition || $alreadyVoted || $isResultRequest ) {
        $maxCount=0;
        for ($i=0; $i < $totalCount; $i++) {
            $optionID = $stats[$i]["OPTION_ID"];
            if ( isset($optionResults["" . $optionID])) {
                $optionResults["" . $optionID]++;
            }else {
                $optionResults["" . $optionID] = 1;
            }
            if ($optionResults["" . $optionID] > $maxCount) {
                $maxCount = $optionResults["" . $optionID];
            }
        }
       $displayResults=true;
    }
    
    //Displays the poll Form.  The file is dependant on pollInit executing first
    if (!isset($pollWidth)) {
        $pollWidth=220;
    }
    $workspaceWidth= $pollWidth - 101;
    $results = fetchRecords("SELECT question, max_selected, min_selected FROM poll where poll_id=" . $pollID, "POLL");

    if (count($results) == 0) {
        echo "<b>POLL " . $pollID . " IS NOT ACTIVE.</b>";
        die();
    }

    $question = $results[0]["QUESTION"];
    $maxSelection = $results[0]["MAX_SELECTED"];
    $minSelection = $results[0]["MIN_SELECTED"];
    $html = "";

//$theForm = newForm('Vote', 'GET', 'poll', 'post', 'pollForm');
//$theForm->addHidden('pollID', $pollID);

$tmp = <<< HTML
    <form name="pollForm" id="pollForm" action=${serverRoot}/plf/plfRGD/index.php method="GET">
    <input type="hidden" name="pollID" value="$pollID"/>
    <input type="hidden" name="module" value="poll"/>
    <input type="hidden" name="func" value="renderWithHeader"/>
    <table class="poll" border="0" cellspacing="0" width="$pollWidth">
        <tr>
            <td colspan="2" class="poll_header" >RGD Survey Question</td>
        </tr>
        <tr class="poll_question">
		<td colspan="2" style="height:10px;"></td>
        </tr>
        <tr class="poll_question">
            <td colspan="2" >$question</td>
        </tr>
        <tr class="poll_question">
		<td colspan="2" style="height:10px;"></td>
        </tr>	
HTML;
$html = $html . $tmp;

    $results = fetchRecords("SELECT option_value, option_id, comment_allowed from poll_option where poll_id=" . $pollID . " order by sort_order", "POLL");
    $num=count($results);

    $i=0;
    while ($i < $num) {
        $optionValue = $results[$i]["OPTION_VALUE"];
        $optionID = $results[$i]["OPTION_ID"];
        $commentAllowed = $results[$i]["COMMENT_ALLOWED"];
        if (!$displayResults) {
$tmp = <<< HTML
            <tr>
                <td class="poll_option" width="25" valign="top">
HTML;
$html = $html . $tmp;
                if ($maxSelection > 1) {
                        //$theForm->addCheckBox('pollQuestion[]', $optionID);
$tmp = <<< HTML
<input type="checkBox" id="option$optionID" name="pollQuestion[]" value="$optionID" />
HTML;
$html = $html . $tmp;
                } else {
                        //$theForm->addRadio('pollQuestion[]', $optionID, false, "");
$tmp = <<< HTML
                    <input type="radio" id="option$optionID" name="pollQuestion[]" value="$optionID" />
HTML;
$html = $html . $tmp;
                }
$tmp = <<< HTML
                </td>
                <td class="poll_option" align="left" width="100%">$optionValue
HTML;
$html = $html . $tmp;
                if ($commentAllowed) {
                    //$theForm->addTextarea("comment" . $optionID, "", 2, 15, 254, false);
$tmp = <<< HTML
                    <br><textarea name="comment_${optionID}" id="comment_${optionID}" value="" onFocus="document.getElementById('option$optionID').checked=true" cols="16" rows="3" wrap=soft></textarea>
HTML;
$html = $html . $tmp;
                }
$tmp = <<< HTML
                </td>
            </tr>
HTML;
$html = $html . $tmp;
        } else {
$tmp = <<< HTML
            <tr>
                <td class="poll_option">$optionValue
HTML;
$html = $html . $tmp;
                // $multFactor = floor(150 / $maxCount);
                $percentage = 0;
                $percentageOfMaxCount=0;
                $numVotes=0;
                if (isset($optionResults["" . $optionID]) && $maxCount != 0) {
                    $percentage = ($optionResults["" . $optionID] / $totalCount) * 100 ;
                    $percentageOfMaxCount = ($optionResults["" . $optionID] / $maxCount) * 100 ;
                    $numVotes= $optionResults["" . $optionID];
                }

                // $ret = $firstNumber / $totalCount;
                if ($maxCount == 0) {
                    $resultBarWidth=0;                
                }else if  ($numVotes == $maxCount) {
                    $resultBarWidth=$workspaceWidth;
                }else{
                    $resultBarWidth = floor($workspaceWidth * ($percentageOfMaxCount / 100));
                }
                $pctFloor = floor($percentage);

$tmp = <<< HTML
                <br>
                <table>
                <tr>
                    <td>
                        <div id="resultBar" style="width:${resultBarWidth}px;" class="poll_result_bar"></div>
                    </td>
                    <td class="poll_result_bar_description">$numVotes votes / ${pctFloor}%</td>
                </tr>
                </table>
                </td>
            </tr>
HTML;
$html = $html . $tmp;

       }
          $i++;
      }

$tmp = <<< HTML
        <tr class="poll_question">
		<td colspan="2" style="height:10px;"></td>
        </tr>    
	<tr>
        <td colspan=2 align="center" class="poll_footer">
HTML;
$html = $html . $tmp;

    if (!$displayResults) {
	    if ($javascriptSubmitFunction == "") {
$tmp = <<< HTML
        <input type="submit" id="submit" name="submit" value="$submitText">&nbsp;&nbsp;
HTML;
	    } else {
$tmp = <<< HTML
<input type="button" id="submit" name="submit" onClick="${javascriptSubmitFunction}" value="$submitText">&nbsp;&nbsp;
HTML;
	    }	

$html = $html . $tmp;
    }

//    [<a href="${serverRoot}/plf/plfRGD/index.php?module=poll&func=renderWithHeader&pollID=${pollID}&viewResults=true">Results</a> &nbsp;|&nbsp; Votes ${totalCount}]

$tmp = <<< HTML
    </td>
</tr>
</table>
</form>
HTML;
$html = $html . $tmp;

return $html;
//. $theForm.quickRender();
}
