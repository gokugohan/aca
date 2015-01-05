<?php
    require_once("bd_tools.php");
    
    function getReviewAuthors($db){
        $query="select * from reviewAuthors;";
        
        var_dump ($query);
        
        if ($db!=null){
            $result=$db->query($query);
            $total=$result->num_rows;
            
            var_dump ($result);
            
            if ($total>0){
                if (DEBUG) echo "<p>There are $total registered reviewer(s).</p>";

                for ($pos=0; $pos<$total; $pos++){
                    $reg=$result->fetch_assoc();
                    echo "<p>reviewAuthorId: ".$reg['reviewAuthorId']." name: ".$reg['name']." entryDate: ".$reg['entryDate']."</p>";
                }//for
                
                return true;
            }//if
            else{
                if (DEBUG) echo "<p>No review authors!</p>";
                return false;
            }//else
        }//if
    }//getReviewAuthors

    $db=DB_connect();
    $ok=getReviewAuthors ($db);
    $db->close();
?>