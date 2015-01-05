<?php
	//addBookmkark.php
	require_once "bd_tools.php";
	
	//data to arrive
	$url=$_POST['url']; //mandatory!
	$title=$_POST['title'];
	$tags=$_POST['tags'];
	
	//booleans that state true or false, depending on if the corresponding values were received
	$recvUrl=isset($url);
	$recvTitle=isset($title);
	$tags=isset($tags);
	
	//was an URL received?
	//TODO: regular expression for URL syntax checking
	if ($recvUrl && is_string($url) && strlen($url)>0){
        
        $tagsArray=explode(" ", $tags); //deprecated since PHP 5.1.3
		
        //$bOk=addBookmark($url, $title);
        $db=DB_connect();
        
        $bOk=addBookmarkAndItsTags($db, $url, $title, $tagsArray);
        
		if ($bOk){
			//feedback that the bookmark was inserted
            $db->close();
		}//if
		else{
			//feedback that the bookmark was NOT inserted
		}//else
	}//if
	else{
		echo fbMsg(ERROR_PARAMETER_URL, MSGS_LANG);
		exit;
	}//else
?>
