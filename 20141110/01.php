<?php
	//var_dump($_POST);
	$nome = $_POST["nome"];
	
	
	function greetings($nome="anonimo"){
		return "Ola ".$nome;
	}
	if(strlen(trim($nome)) == 0){
		echo greetings();
	}else{
		echo greetings($nome);
	}
?>