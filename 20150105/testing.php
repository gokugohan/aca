<?php
	//testing.php
	echo "<p>@TESTING.PHP - before require_once</p>";
	
	require_once "bd_tools.php";
	
	echo "<p>@TESTING.PHP - after require_once</p>";	
	
	function TEST_addBookmark
	(
		$db,
		$url="http://arturmarques.com/",
		$title="Artur Marques\' site"
	)
	{
		addBookmark($db, $url, $title); //ignore TAGs for now
	}//TEST_addBookmark
	
	$db=DB_connect();
	
	echo "<p>@TESTING.PHP - after DB_connect</p>";
	var_dump ($db);
	
	TEST_addBookmark($db);
?>
