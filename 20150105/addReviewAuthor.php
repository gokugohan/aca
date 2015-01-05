<?php
    require_once ("bd_tools.php");
    
    if (NO_BROWSER){
        $reviewAuthorName='Artur';
        $password='1234';
    }
    else{
        $reviewAuthorName=$_POST['reviewAuthorName'];
        $password=$_POST['password'];
    }
    
    if (DEBUG){
        echo "<p>Received: reviewAuthorName= $reviewAuthorName ; password= $password</p>";
    }
     
     if (DEBUG) echo "<p>Before db=DB_connect();</p>";
     $db=DB_connect();
     if (DEBUG) echo "<p>AFTER db=DB_connect();</p>";
     
     if (DEBUG) echo "<p>Before ok=newReviewer(...);</p>";
     $ok=newReviewer($db, $reviewAuthorName, $password);
     if (DEBUG) echo "<p>AFTER ok=newReviewer(...)=$ok;</p>";     
     
     if ($ok)
        echo "<p>Review author insert OK.";
     else
        echo "<p>Review author insert FAIL.";
        
     $db->close();
?>
