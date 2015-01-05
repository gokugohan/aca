<?php
    require_once("bd_tools.php");
    
    function getBookmarks($db){
        $query="select * from bookmarks;";
        
        var_dump ($query);
        
        if ($db!=null){
            $result=$db->query($query);
            $total=$result->num_rows;
            
            var_dump ($result);
            
            if ($total>0){
                if (DEBUG) echo "<p>There are $total registered bookmark(s).</p>";

                for ($pos=0; $pos<$total; $pos++){
                    $reg=$result->fetch_assoc();
                    $bId=$reg["bookmarkId"];
                    $bUrl=$reg["url"];
                    $bTitle=$reg["title"];
                    $bEntryDate=$reg["entryDate"];
                    $bLastUpdate=$reg["lastUpdate"];
                    echo "<p>Bookmark (id, url, title, entry, last update): '$bId', '$bUrl' , '$bTitle', '$bEntryDate' , '$bLastUpdate'</p>";
                }//for
                
                return true;
            }//if
            else{
                if (DEBUG) echo "<p>No bookmarks!</p>";
                return false;
            }//else
        }//if
    }//getBookmarks

    $db=DB_connect();
    $ok=getBookmarks ($db);
    $db->close();
?>