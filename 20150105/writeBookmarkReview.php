<?php
    //writeBookmarkReview
    session_start(); //inform PHP that a session is going on and that the $_SESSION superglobal must be loaded
    var_dump ($_SESSION);
    
    $reviewAuthorName=$_SESSION['reviewAuthorName'];
    
	if (isset ($reviewAuthorName)){
    	echo "<h1>Reviewer $reviewAuthorName is logged in.</h1>";
    	echo "<p>Please <a href=\"reviewAuthorWrite.php\">write your review</a>.</p>";
    }
    else{
    	echo "<h1>Sorry, no review author logged in.</h1>";
    	//echo "<p>Please <a href=\"reviewAuthorLogin.php\">log in</a>.</p>";
    	echo "<p>Please <a href=\"bookmarks.html#login\">log in</a>.</p>";
	}
?>
