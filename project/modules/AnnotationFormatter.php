<?php

Class AnnotationFormatter {

  function toXML($chromosome, $start_pos, $stop_pos, $type, $symbol, $url, $color, $rgdID) {
        $output = "<feature>\n";
        $output .= "<chromosome>" . $chromosome . "</chromosome>\n";
        $output .= "<start>" . $start_pos . "</start>\n";
        $output .= "<end>" . $stop_pos . "</end>\n";
        $output .= "<type>". $type . "</type>\n";
        $output .= "<label>" . $symbol . "</label>\n";
        $output .= "<link>" . $url . $rgdID . "</link>\n";
        $output .= "<color>" . $color . "</color>\n";
        $output .= "</feature>\n";
        return $output;
    }

    function toTabDelimited($chromosome, $startPos, $stopPos, $type, $symbol, $link, $color, $rgdID) {
       return $chromosome . "\t" . $rgdID . "\t" . $type . "\t" . $symbol . "\t" . $startPos . "\t" . $stopPos . "\trat\n";
    }

    function toHTML($annotationArray) {
        return "";
    }
}
