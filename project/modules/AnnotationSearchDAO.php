<?php

Class AnnotationSearchDAO {

    function getXML($term, $species) {
        $col = "gviewer_xml_rat";

        if ($species == 1) {
            $col = "gviewer_xml_human";

        }else if ($species == 2) {
            $col = "gviewer_xml_mouse";
        }

        $sql = "SELECT $col FROM  
                    portal_cat1 
                WHERE 
                    portal_ver_id  in 
                    ( 
                        SELECT 
                            pv.portal_ver_id 
                        FROM 
                            portal_ver1 pv , 
                            portal1 p 
                        WHERE 
                            portal_ver_status = 'Active' 
                            and pv.portal_key = p.portal_key 
                            and p.portal_type = 'Ontology' 
                    ) 
                    and category_term_acc = '$term'";

         //echo $sql;
         return fetchField($sql, "RGD");
    }

    function getQTLS() {
      // Query here for QTLS and display them
      $sqlQTLS = "select q.rgd_id, q.qtl_symbol,  m.chromosome, m.start_pos, m.stop_pos
            from qtls q, maps_data m
            where
            q.rgd_id = m.rgd_id
            and m.map_key = 60
            and q.rgd_id in (
                select unique f.annotated_object_rgd_id
                from full_annot f
                where
                f.rgd_object_key = 6 and f.term_acc in (select t.term_acc from ont_terms t where " . $this->getTermSQL() . ")) order by q.qtl_symbol, m.start_pos";
		// echo $sqlQTLS;
        return fetchRecords($sqlQTLS, "RGD");
    }


    function getGenes() {
        // Now get all genes that are annotated to this list of terms
        $sqlGenes = "select g.rgd_id, g.gene_symbol,  m.chromosome, m.start_pos, m.stop_pos
        from genes g, maps_data m
        where
        g.rgd_id = m.rgd_id
        and m.map_key = 60 and ";

        $sqlGenes = $sqlGenes . " g.rgd_id in (
            select unique f.annotated_object_rgd_id
            from full_annot f
            where
            f.rgd_object_key = 1 and f.term_acc in (select t.term_acc from ont_terms t where " . $this->getTermSQL() . ")) order by g.gene_symbol, m.start_pos";


         //echo $sqlGenes;
         return fetchRecords($sqlGenes, "RGD");

    }

    function getStrains() {
        // Now get all genes that are annotated to this list of terms
        $sqlStrains = "select s.rgd_id, s.strain_symbol
        from strains s
        where ";

        $sqlStrains = $sqlStrains . " s.rgd_id in (
            select unique f.annotated_object_rgd_id
            from full_annot f
            where
            f.rgd_object_key = 5 and f.term_acc in (select t.term_acc from ont_terms t where " . $this->getTermSQL() . ")) order by s.strain_symbol";

		// echo $sqlStrains;
        return fetchRecords($sqlStrains, "RGD");

    }

    function getTerms() {
        $sql = "SELECT t.term, t.term_acc, o.ont_name, c.annot_obj_cnt_w_children_rat, c.annot_obj_cnt_w_children_mouse, c.annot_obj_cnt_w_children_human
		FROM ont_terms t, portal_cat1 c, ontologies o 
		WHERE t.term_acc=c.category_term_acc AND t.ont_id=o.ont_id AND c.portal_ver_id IN (SELECT 
            pv.portal_ver_id 
        FROM 
            portal_ver1 pv, 
            portal1 p 
        WHERE 
            portal_ver_status = 'Active' 
            AND pv.portal_key = p.portal_key 
            AND p.url_name in( 'go', 'do', 'mp', 'pw', 'bo') 
 ) AND NVL(c.annot_obj_cnt_w_children_rat,0)+NVL(c.annot_obj_cnt_w_children_mouse,0)+NVL(c.annot_obj_cnt_w_children_human,0)>0 
   AND " . $this->getTermSQL() . "order by o.ont_name, t.term";
        //echo $sql;
        return fetchRecords($sql, "RGD");
    }


function getTermSQL() {

    $sqlTerms = "SELECT term_acc FROM ont_terms t WHERE ";

    for ($i=0; $i< count($_REQUEST['term']); $i++) {
        //replace wild card characters
        $_REQUEST['term'][$i] = str_replace("*", "%", $_REQUEST['term'][$i]);

        //defend against sql injection
        $_REQUEST['term'][$i] = str_replace(";", "", $_REQUEST['term'][$i]);
        $_REQUEST['term'][$i] = str_replace("'", "''", $_REQUEST['term'][$i]);
        $_REQUEST['term'][$i] = str_replace("chr(", "", $_REQUEST['term'][$i]);
        $_REQUEST['term'][$i] = str_replace("CHR(", "", $_REQUEST['term'][$i]);

        //if the term is blank,  lets just have it not find anything
        if (trim($_REQUEST['term'][$i]) == "") {
            $_REQUEST['term'][$i] = "StringThatWillNeverBeFound!!!&&**DD";
        }

        $sqlTerms .= " (upper(t.term) like upper('%" . trim($_REQUEST['term'][$i]);
        $sqlTerms .= "%') and t.ont_id in (";

        $ontologies = array("go", "do", "bo", "po", "wo");
        $firstRun = true;
        foreach ($ontologies as $ontology) {
            if (isset($_REQUEST[$ontology][$i]) && $_REQUEST[$ontology][$i] != -1) {
                if ($firstRun) {
                    $firstRun=false;
                }else {
                    $sqlTerms .= ",";
                }
                if( $ontology=='go' ) { 
                  $sqlTerms .= "'BP','CC','MF'";
                } elseif( $ontology=='do' ) {
                  $sqlTerms .= "'RDO'";
                } elseif( $ontology=='bo' ) {
                  $sqlTerms .= "'NBO'";
                } elseif( $ontology=='po' ) {
                  $sqlTerms .= "'MP'";
                } elseif( $ontology='wo' ) {
                  $sqlTerms .= "'PW'";
               }
            }
        }
        $sqlTerms .= ")) ";

       // echo count($_REQUEST["term"]);
        if (($i + 1) < count($_REQUEST["term"])) {
            if (isset($_REQUEST["op"])) {
                $sqlTerms .= $_REQUEST["op"][$i];
            }
        }
    }

	// modified to include all children terms as well
    return " term_acc in (
	    SELECT child_term_acc FROM ont_dag
		START WITH child_term_acc IN( ".$sqlTerms.")
        CONNECT BY PRIOR child_term_acc=parent_term_acc ) ";
}
}//end class
?>
