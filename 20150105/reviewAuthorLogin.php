<?php
	//example of update:
	//update reviewAuthors set reviewAuthors.name="Matilde Marques" where reviewAuthors.name="Artur Marques";


	//reviewAuthorLogin
	session_start();
	var_dump ($_SESSION);
	
	require_once "bd_tools.php";
	
	if (NO_BROWSER){
		$reviewAuthorName='Artur';
		$password='1234';
	}
	else{
		$reviewAuthorName=$_POST['reviewAuthorName'];
		$password=$_POST['password'];	
	}
	
	$c1=isset($reviewAuthorName);
	$c2=isset($password);
	
	if ($c1 && $c2){
		$db=DB_connect();
		if ($db!=false){
			$success=login($db, $reviewAuthorName, $password);
			if ($success){
				$_SESSION['reviewAuthorName']=$reviewAuthorName;
				echo "<h1>$reviewAuthorName is logged in!</h1>";
				
				var_dump ($_SESSION);
			}//if
			else{
				echo "<p>Login FAILED!</p>";
			}
		}//if
		else
			echo "<p>No connection to database...</p>";
	}//if
	else{
		echo "<p>Review author name and/or password not received.</p>";
		exit;
	}
?>
