<?php
	define ('DEBUG', true); //while in development set to true
    define ('MSGS_LANG', 'ENG'); //'ENG' for messages in english, 'PT' for messages in EUR-PT
    define ('NO_BROWSER', true); //for debugging outside the browser
    define ("DEBUG", true);
    
    //session_start(); //the files that include bd_tools do NOT have to do a session_start (in fact that will spark a warning)

//_.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-.
//START: SYMBOLIC CONSTANTS for MySQL tables creation
    //DROP TABLE IF EXISTS "TABLE NAME" ;
    
    //2015-01-04
    //SET FOREIGN_KEY_CHECKS = 0;
    $uninstall = array(
        "drop table if exists bookmarks_v1.bookmarkTags",
        "drop table if exists bookmarks_v1.tags",
        "drop table if exists bookmarks_v1.reviewAuthors",
        "drop table if exists bookmarks_v1.reviews",
        "drop table if exists bookmarks_v1.bookmarks"
    );
    
    //2015-01-04
    function uninstaller (){
        $db=DB_connect();
        if ($db!==false){
            $instructions=$GLOBALS['uninstall'];
            foreach ($instructions as $i){
                $db->query($i);
                $e=mysqli_errno($db);
                $eM=mysqli_error($db);
                
                $msg="executed $i with error $e / $eM".PHP_EOL;
            }//foreach
            $db->close();
        }//if
    }//uninstaller

    //besides the data explicitly assumed in the bookmarks table, a bookmark also can have tags, but this is captured via the "bookmarksTags" table
    define ("CREATE_BOOKMARKS_TABLE","
    CREATE  TABLE bookmarks_v1.bookmarks
    (
        bookmarkId INT NOT NULL AUTO_INCREMENT ,
        url VARCHAR(512) NOT NULL ,
        title VARCHAR(128) NULL ,
        entryDate DATETIME NOT NULL ,
        lastUpdate DATETIME NOT NULL ,
        PRIMARY KEY (bookmarkId)
    );
    ");

    //reviewAuthors holds people who write bookmark reviews: they have a name, a password, and 1st registered at some entryDate
    define ("CREATE_REVIEWAUTHORS_TABLE", "
        CREATE  TABLE bookmarks_v1.reviewAuthors
        (
            reviewAuthorId INT NOT NULL AUTO_INCREMENT,
            name VARCHAR(256) NOT NULL ,
            password VARCHAR(16) NOT NULL ,
            entryDate DATETIME NOT NULL ,
            PRIMARY KEY (reviewAuthorId)
        );
    ");

    //reviews are texts written by someone registered in the reviewAuthors table about some bookmarks' entry; they have a text, an entryDate and a lastUpdate date
    define ("CREATE_REVIEWS_TABLE","
        CREATE  TABLE bookmarks_v1.reviews
        (
            reviewId INT NOT NULL AUTO_INCREMENT ,
            review TEXT NULL ,
            entryDate DATETIME NOT NULL ,
            lastUpdate DATETIME NOT NULL ,
            _bookmarkId_ INT NOT NULL ,
            _reviewAuthorId_ INT NOT NULL ,
            PRIMARY KEY (reviewId) ,

            INDEX fkBookmarkId_idx (_bookmarkId_ ASC) ,
            INDEX fkReviewAuthorId_idx (_reviewAuthorId_ ASC) ,

            CONSTRAINT fkBookmarkId
                FOREIGN KEY (_bookmarkId_ )
                REFERENCES bookmarks_v1.bookmarks (bookmarkId )
                ON DELETE NO ACTION
                ON UPDATE NO ACTION,
            CONSTRAINT fkReviewAuthorId
                FOREIGN KEY (_reviewAuthorId_ )
                REFERENCES bookmarks_v1.reviewauthors (reviewAuthorId )
                ON DELETE NO ACTION
                ON UPDATE NO ACTION
        );
    ");

    //tags is a table that holds textual entries named "tags", which have an initial entryDate
    define ("CREATE_TAGS_TABLE", "
        CREATE  TABLE bookmarks_v1.tags
        (
            tagId INT NOT NULL AUTO_INCREMENT ,
            tag VARCHAR(45) NOT NULL ,
            entryDate DATETIME NOT NULL ,
            PRIMARY KEY (tagId)
        );
    ");

    //N:N relations, such as "a bookmark can have many tags and a tag can be had by many bookmarks" always imply an artificial table whose sole rows are pairs of foreign keys
    //MySQL errno 121 might occur: You will get this message if you're trying to add a constraint with a name that's already used somewhere else
    define ("CREATE_BOOKMARKS_TAGS_TABLE", "
        CREATE  TABLE bookmarks_v1.bookmarkTags (
            bookmarkId INT NOT NULL ,
            tagId INT NOT NULL ,
            PRIMARY KEY (bookmarkId, tagId) ,

            INDEX fkBookmarkId_idx (bookmarkId ASC) ,
            INDEX fkTagId_idx (tagId ASC) ,

            CONSTRAINT fkBookmarkIdFromBookmarksTags
            FOREIGN KEY (bookmarkId )
            REFERENCES bookmarks_v1.bookmarks (bookmarkId )
            ON DELETE NO ACTION
            ON UPDATE NO ACTION,

            CONSTRAINT fkTagIdFromBookmarksTags
            FOREIGN KEY (tagId )
            REFERENCES bookmarks_v1.tags (tagId )
            ON DELETE NO ACTION
            ON UPDATE NO ACTION);
        ");

//END: SYMBOLIC CONSTANTS for MySQL tables creation
//_.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-.

    /*
    generic function for database table creation queries
    it receives the string for the query to be executed
    executes the query
    returns true if everything goes ok
    returns false is something fails
    if the DEBUG symbolic constant is set to true, it echoes feedback debug messages
    */
    function createTable ($db, $tableCreationStatement){
        if ($db){
            $result=$db->query($tableCreationStatement);
            $error=mysqli_errno($db); //return code for most recent call
            $errorMsg=mysqli_error($db); //message for most recent error
            if ($error===0) return true;
            else{
                //error on create table
                if (DEBUG){
                    $fbMsg=fbMsg('ERROR_DB_CREATE_TABLE',MSGS_LANG).': '.$errorMsg."\nWhen running statement: ".$tableCreationStatement;
                    echo $fbMsg;
                }
                return false;
            }
        }//if
        else{
            //problem with $db connection
            if (DEBUG){
                $fbMsg=fbMsg('ERROR_DB_POINTER',MSGS_LANG);
                echo $fbMsg;
            }
            
            return false;
        }
    }//createTable

//_.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-.    
    /*---------------------------------------------------------------------------------------------------------------------*/
    function fbMsg($code, $lang="ENG"){
        //TODO: learn how to code any char in PHP, no matter the presentation technology    
        $MSGS_PT=array(
            'ERROR_NONE' => 'Sem erros.',
            'ERROR_DB_CREATE_TABLE' => 'Nao foi possivel criar a tabela',
            'ERROR_DB_POINTER' => 'Nao foi recebido um ponteiro valido para a BD',
            'ERROR_DB_CONNECT' => 'Ligacao a base de dados falhou.',
            'ERROR_DB_PARAMETERS' => 'Nao foi recebida a ligacao a BD ou algum parametro para a ligacao, como nome ou password.',
            'ERROR_DB_INSERT' => 'Operacao de insert sobre a BD falhou.',
            'ERROR_DB_REVIEWER_EXISTS' => 'Reviewer existe. Insert falhou.',
            'ERROR_PARAMETER_URL' => 'No URL received *or* wrong data type for URL *or* wrong syntax for URL string.',
            'OK_DB_INSERT' => 'Bookmark was inserted into database.'
        );
        
        $MSGS_ENG=array(
            'ERROR_NONE' => 'No errors.',
            'ERROR_DB_CREATE_TABLE' => 'Could not create table',
            'ERROR_DB_POINTER' => 'Invalid pointer to DB',
            'ERROR_DB_CONNECT' => 'Connection to DB failed.',
            'ERROR_DB_PARAMETERS' => 'No DB connection received or some connection parameter, like name/password was missing.',
            'ERROR_DB_INSERT' => 'Insert into DB failed.',
            'ERROR_DB_REVIEWER_EXISTS' => 'Reviewer exists. Insert failed.',
            'ERROR_PARAMETER_URL' => 'URL NAO recebido *ou* tipo de dados errado para o URL *ou* URL com sintaxe invalida.',
            'OK_DB_INSERT' => 'Bookmark foi inserido na base de dados.'
        );
        
        $MSGS=array(
            'PT' => $MSGS_PT,
            'ENG' => $MSGS_ENG
        );
        
        return $MSGS[$lang][$code];
    }//fbMsg

//_.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-.    
    /*---------------------------------------------------------------------------------------------------------------------*/
	function DB_connect(
		$server="localhost",
		$user="user_bookmarks",
		$password="1234",
		$schema="bookmarks_v1",
		$port=3306,
        &$fbMsg=""
	)
	{
        $fbMsg=fbMsg('ERROR_NONE', MSGS_LANG);
		$db=null;
		
		//$db=new mysqli($server, $user, $password, $schema, $port);
        $db=mysqli_connect($server, $user, $password, $schema, $port); //opens a new connection to the mysql server - should return false if no connection is possible
		$error=mysqli_connect_errno(); //returns the code from last connect call (error>0 is a problem)
        $fbMsg=mysqli_connect_error(); //returns a description of the last connect error
		
        //have a db link and no error?
		if (($db!=false) && ($error==0)){
			return $db;
		}//if
		else{
            if (DEBUG){
            	$fbMsg=fbMsg('ERROR_DB_CONNECT',MSGS_LANG).': '.$fbMsg;
            	echo $fbMsg;
			}
			return false;
        }
	}//DB_connect

//_.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-.    
    //2013-07-17
    function tagExists ($db, $tag){
        if ($db!=null && is_string($tag) && strlen($tag)>0){
            $query="select * from tags where tags.tag='$tag';";
            
            if (DEBUG) echo "<p>@tagExists<br>doing query=$query</p>";
                
            $result=$db->query($query);
            $error=mysqli_errno($db); //return code for most recent call
            $errorMsg=mysqli_error($db); //message for most recent error
            
            if (DEBUG){
                echo "result= ";
                var_dump ($result)."<br>";
                echo "<p>error= $error ; fbMsg= $errorMsg</p>";
                echo "<p>#result(s)= $result->num_rows</p>";
                echo "<p>DB affected rows= $db->affected_rows</p>";
            }//if
                        
            //NOTICE: there will always be a response $result structure, hence you DO NOT check for results with !$result
            //if (!$result)
            if (!$result || $result->num_rows==0){
                if (DEBUG) echo "<p>@tagExists ; returning false (meaning tag does NOT exist);</p>";
                return false; //No such tag
            }
            else{
                $row=$result->fetch_assoc();
                $tagId=$row["tagId"];
                if (DEBUG) echo "<p>@tagExists ; returning $tagId (meaning tag already in DB with this tagId);</p>";                
                return $tagId; //tag is already in the DB
            }
        }//if
        else{
            $fbMsg=fbMsg('ERROR_DB_PARAMETERS',MSGS_LANG);
            if (DEBUG) echo $fbMsg;
            return false;
        }//else
    }//tagExists

//_.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-.	
    /*---------------------------------------------------------------------------------------------------------------------*/
	function reviewerExists ($db, $name, &$fbMsg=""){
		if ($db!=null && is_string($name) && strlen($name)>0){
			$query="select * from reviewauthors where reviewauthors.name='$name';";
            
            if (DEBUG) echo "<p>@reviewerExists<br>doing query=$query</p>";
				
			$result=$db->query($query);
            $error=mysqli_errno($db); //return code for most recent call
            $errorMsg=mysqli_error($db); //message for most recent error
			
            if (DEBUG){
                echo "result= ";
                var_dump ($result)."<br>";
                echo "<p>error= $error ; fbMsg= $errorMsg</p>";
                echo "<p>#result(s)= $result->num_rows</p>";
                echo "<p>DB affected rows= $db->affected_rows</p>";
            }//if
						
			//NOTICE: there will always be a response $result structure, hence you DO NOT check for results with !$result
			//if (!$result)
			if (!$result || $result->num_rows==0){
                if (DEBUG) echo "<p>@reviewerExists ; returning false (meaning reviewer does NOT exist);</p>";
				return false; //No such reviewer
            }
			else{
                if (DEBUG) echo "<p>@reviewerExists ; returning true (meaning reviewer already in DB);</p>";                
				return true; //reviewer is already in the DB
            }
		}//if
		else{
            $fbMsg=fbMsg('ERROR_DB_PARAMETERS',MSGS_LANG);
            if (DEBUG) echo $fbMsg;
            return false;
		}//else
	}//reviewerExists
	
//_.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-.
    /*---------------------------------------------------------------------------------------------------------------------*/
	function newReviewer($db, $name, $password){
        $fbMsg="";
        $reviewerExists=reviewerExists($db, $name, $fbMsg);
        if (DEBUG) echo "<p>@newReviewer ; reviewerExists=$reviewerExists</p>";
        
		if ($db!=null && is_string($name) && is_string($password) && strlen($name)>0 && !$reviewerExists){
            $date=date("Y-m-d-G-i-s");
			$query="insert into reviewauthors values (null, '$name', '$password', '$date');";
            
            if (DEBUG) echo "<p>@newReviewer ; query=$query </p>";
			
			$result=$db->query($query);
            $error=mysqli_errno($db); //return code for most recent call
            $errorMsG=mysqli_error($db); //message for most recent error
                        
            if (DEBUG){
                echo "result= ";
                var_dump ($result)."<br>";
                echo "<p>error= $error ; errorMsg= $errorMsg</p>";
                echo "<p>#result(s)= $result->num_rows</p>";
                echo "<p>DB affected rows= $db->affected_rows</p>";
            }//if
            
			if($error==0 && $db->affected_rows==1){
                if (DEBUG) echo "<p>@newReviewer ; returning #db->affected_rows= $db->affected_rows;</p>";                            
				return $db->affected_rows; //return how many (1) inserted reviewAuthors
			}//if
			else{
                $fbMsg=fbMsg('ERROR_DB_INSERTS',MSGS_LANG).": ".$errorMsg;                
                if (DEBUG){
                    echo $fbMsg;
                    echo "<p>@newReviewer ; return false;</p>";
                }//if
                return false;
            }//else
		}//if
		else{
            $fbMsg=fbMsg('ERROR_DB_REVIEWER_EXISTS',MSGS_LANG);
            if (DEBUG){
                echo $fbMsg;
                echo "<p>@newReviewer ; return false;</p>";            
            }
            return false;
        }//else
	}//newReviewer

//_.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-.	
    /*---------------------------------------------------------------------------------------------------------------------*/
	function passwordOk($db, $reviewerName, $password){
		if ($db!=null && is_string($reviewerName) && is_string($password) && strlen($reviewerName)>0 && reviewerExists($db, $reviewerName)){
			$query="select * from reviewauthors where (reviewauthors.name='$reviewerName' and reviewauthors.password='$password')";
			
            $result=$db->query($query);
            $error=mysqli_errno($db); //return code for most recent call
            $errorMsg=mysqli_error($db); //message for most recent error
            
            if (DEBUG){
                echo "result= ";
                var_dump ($result)."<br>";
                echo "<p>error= $error ; errorMsg= $errorMsg</p>";
                echo "<p>#result(s)= $result->num_rows</p>";
                echo "<p>DB affected rows= $db->affected_rows</p>";
            }//if

			if ($error==0 && $result->num_rows==1)
				return true; //login/pwd ok
			else
				return false; //login/pwd failed
		}//if
		else
			return false;	
	}//passwordOk

//_.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-.
	/*---------------------------------------------------------------------------------------------------------------------*/
    function login($db, $reviewAuthorName, $password){
		if (DEBUG) echo "<p>reviewAuthorName= $reviewAuthorName ; password=$password</p>";
		
        if ($db!=null && is_string($reviewAuthorName) && is_string($password) && strlen($reviewAuthorName)>0){
			$ok=passwordOk($db, $reviewAuthorName, $password);

			if (DEBUG) echo "<p>password ok= ".var_dump($ok)."</p>";
				
			if ($ok){			
				//session_start(); //must happen before any other data being sent to client, so it does NOT make sense here
				$_SESSION['reviewAuthorName']=$reviewAuthorName;
				
				if (DEBUG){
					echo "session id= ".PHPSESSID;
					var_dump($_SESSION);
					echo "<p>Review author login OK.</p>";
				}
				
				return true;
			}//if
			else{
				if (DEBUG) echo "<p>Review author login FAILED.</p>";
				return false;
			}//else
		}//if
		else{
			if (DEBUG){
            	$fbMsg=fbMsg('ERROR_DB_PARAMETERS',MSGS_LANG);
            	echo $fbMsg;
			}
            return false;
        }//else
	}//login
	
//_.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-.
	/*---------------------------------------------------------------------------------------------------------------------*/
	function addBookmark($db, $url, $title=""){
		if (DEBUG) echo "<p>@addBookmark (db, $url, $title)</p>";
		
		if ($db!=null && $url!=null && is_string($url) && strlen($url)>0){ //todo: proper syntax check for URL
			$entryDate=$lastUpdate=date("Y-m-d-G-i-s");
			
			$queryToInsertBookmark="insert into bookmarks values (null, '$url', '$title', '$entryDate', '$lastUpdate');";
			$result=$db->query($queryToInsertBookmark); //in a successful insert, the result will be true
			
			$error=mysqli_errno($db);
			$errorMsg=mysqli_error($db);
			
			if ($result==true && $error==0 && $db->affected_rows==1){
				if (DEBUG) echo fbMsg ('OK_DB_INSERT', MSGS_LANG);
				return $insertedId=$db->insert_id; //return the id of the inserted row, as long as it has a AUTO_INCREMENT field
				//bookmark inserted
				//return true;
			}//
			else{
				//bookmark NOT inserted
				if (DEBUG) echo fbMsg('ERROR_DB_INSERT',MSGS_LANG);
				return $insertedId=$db->insert_id; //when nothing is inserted, the return will be zero, which corresponds to bool false
				//return false;
			}//else
			
		}//if
		else{
			//no database link
			if (DEBUG) echo fbMsg('ERROR_DB_PARAMETERS',MSGS_LANG);
			return false;
		}//else
	}//addBookmark

//_.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-.
    /*---------------------------------------------------------------------------------------------------------------------*/    
    /*
    addTag receives
    - an open established connection to a database with a tags table with the proper structure and permissions previously set
    - a tag which is a string
    this is the simplest of the tables in the "bookmarks" project: it is a mere list of tags
    adds the tag to the table

    addTag returns
    - if it succeeds, the insert_id of the new row; ie the new row number
    - if it fails, the insert_id of the operation, 0 (zero), which is interpreted as false in boolean operations
    
    NOTES:
    - the relationship between a tag and bookmark(s), and vice-versa, is expressed in another table: bookmarkTags
    - the tag is here accepted as any string, but the original user interface separated tags with white spaces, so there shouldn't be white spaces in tags entered via that interface
    */
    function addTag ($db, $tag){
        if (DEBUG) echo "<p>@addTag (db, $tag)</p>";
        
        if ($db!=null && $tag!=null && is_string($tag) && strlen($tag)>0){
            $entryDate=date("Y-m-d-G-i-s");
            $queryToInsertTag="insert into tags values (null, '$tag', '$entryDate');";
            $result=$db->query($queryToInsertTag); //in a successful insert, the result will be true
            
            $error=mysqli_errno($db);
            $errorMsg=mysqli_error($db);
            
            if ($result==true && $error==0 && $db->affected_rows==1){
                if (DEBUG) echo fbMsg ('OK_DB_INSERT', MSGS_LANG);
                return $insertedId=$db->insert_id; //return the id of the inserted row, as long as it has a AUTO_INCREMENT field
                //tag inserted
                //return true;
            }//
            else{
                //tag NOT inserted
                if (DEBUG) echo fbMsg('ERROR_DB_INSERT',MSGS_LANG);
                return $insertedId=$db->insert_id; //when nothing is inserted, the return will be zero, which corresponds to bool false
                //return false;
            }//else
            
        }//if
        else{
            //no database link
            if (DEBUG) echo fbMsg('ERROR_DB_PARAMETERS',MSGS_LANG);
            return false;
        }//else        
    }//addTag

//_.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-.
//2013-07-17
    function addTagIfItDoesNotExist ($db, $tag){
        $tagId=tagExists($db, $tag);
        if ($tagId===false){
            $tagId=addTag($db, $tag);
        }//if
        return $tagId;
    }//addTagIfItDoesNotExist

//_.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-.
//2013-07-17
    function dbRelateBookmarkWithTag ($db, $bId, $tId, &$fbMsg=""){
        $fbMsg="";
        
        if (DEBUG) echo "<p>@dbRelateBookmarkWithTag</p>";
        
        if ($db!=null){
            $query="insert into bookmarkTags values ('$bId', '$tId');";
            
            if (DEBUG) echo "<p>@dbRelateBookmarkWithTag ; query=$query </p>";
            
            $result=$db->query($query);
            $error=mysqli_errno($db); //return code for most recent call
            $errorMsg=mysqli_error($db); //message for most recent error
                        
            if (DEBUG){
                echo "result= ";
                var_dump ($result)."<br>";
                echo "<p>error= $error ; errorMsg= $errorMsg</p>";
                echo "<p>#result(s)= $result->num_rows</p>";
                echo "<p>DB affected rows= $db->affected_rows</p>";
            }//if
            
            if($error==0 && $db->affected_rows==1){
                if (DEBUG) echo "<p>@dbRelateBookmarkWithTag ; returning #db->affected_rows= $db->affected_rows;</p>";                            
                return $db->affected_rows; //return how many (1) inserted relations
            }//if
            else{
                $fbMsg=fbMsg('ERROR_DB_INSERTS',MSGS_LANG).": ".$errorMsg;                
                if (DEBUG){
                    echo $fbMsg;
                    echo "<p>@dbRelateBookmarkWithTag ; return false;</p>";
                }//if
                return false;
            }//else
        }//if
    }//dbRelateBookmarkWithTag

//_.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-._.-^-.
//2013-07-17
/*
new functions because of this function:
- addTagIfItDoesNotExist
- dbRelateBookmarkWithTag
*/
    function addBookmarkAndItsTags ($db, $url, $title="", $tagsArray){
        if (DEBUG) echo "<p>@addBookmarkAndItsTags (db, $url, $title, $tagsArray)</p>";

        $addBookmarkResult=addBookmark($db, $url, $title);
        $receivedTagsAsArray=($tagsArray!=null) && is_array($tagsArray);

        if ($addBookmarkResult && $receivedTagsAsArray){
            $results=array();
            foreach ($tagsArray as $tag){
                $addTagResult=addTagIfItDoesNotExist($db, $tag);
                
                $bookmarkId=$addBookmarkResult; //which bookmark?
                $tagId=$addTagResult; //which tag?

                $relateBookmarkWithTagResult=dbRelateBookmarkWithTag($db, $bookmarkId, $tagId);

                if (DEBUG){
                    $s+="<hr>result= $relateBookmarkWithTagResult for the operation relating:<br>";
                    $s+="bookmark url=$url ; title=$title ; id=$bookmarkId<br>";
                    $s+="with<br>";
                    $s+="tag $tag id=$tagId<br>";
                    echo $s;
                }//if DEBUG
                
                $relationship=array($relateBookmarkWithTagResult, $bookmarkId, $tagId); //a trio that captures the relationship result
                $results[]=$relationship; //an array of arrays, each a trio
            }//foreach

            if (DEBUG){
                $s+="<hr>results for all the tags for bookmark url=$url ; id=$bookmarkId<br>";
                foreach ($results as $trio){
                    $s+="result= ".$trio[0]." bookmark=".$trio[1]." tag=".$trio[2]."<br>";
                }//foreach
                echo $s;
            }//if DEBUG

            return $results;
        }//if
        else{
            if (DEBUG){
                $s="<hr>Failure (returning false) @ addBookmarkAndItsTags<br>";
                $s+="addBookmarkResult: $addBookmarkResult<br>";
                $s+="receivedTagsAsArray: $receivedTagsAsArray<br>";
                echo $s;
            }//if DEBUG
            return false;
        }//else
    }//addBookmarkAndItsTags
	
	/*---------------------------------------------------------------------------------------------------------------------*/
	
	function htmlSelectReviewAuthor(){
		$db=DB_connect();
		if ($db){
			$reviewers=array(); //will hold all the reviewers' names
			$html="<select name='selReviewAuthor'>";
			
			$query="select * from reviewAuthors order by name asc"; //http://www.w3schools.com/sql/sql_orderby.asp
			$result=$db->query($query);
			$error=mysqli_errno($db);
			$errorMsg=mysqli_error($db);
			
			if ($error==0){
				$howManyAuthors=$result->num_rows;
				for ($r=0; $r<$result->num_rows; $r++){
					$assoc=$result->fetch_assoc();
					$reviewer=$assoc['name'];
					$reviewers[$r]=$reviewer;
				}//for
				
				for ($r=0; $r<$result->num_rows; $r++)
					$html.="<option>".$reviewers[$r]."</option>";
					
				return $html;
			}//if
			$html.="</select>";
			return $html;
		
		}//if
	}//htmlSelectReviewAuthor
	
	/*---------------------------------------------------------------------------------------------------------------------*/
	
	function htmlSelectBookmark(){
	}
	
	/*---------------------------------------------------------------------------------------------------------------------*/

    function createRequiredDBTables(){
        $db=DB_connect();
        if ($db){
            $result=createTable ($db, CREATE_BOOKMARKS_TABLE);
            $result=createTable ($db, CREATE_REVIEWAUTHORS_TABLE);
            $result=createTable ($db, CREATE_REVIEWS_TABLE);
            $result=createTable ($db, CREATE_TAGS_TABLE);
            $result=createTable ($db, CREATE_BOOKMARKS_TAGS_TABLE);
        }//if
        $db->close();
    }//createRequiredDBTables

    function testAddBookmarkAndItsTags(){
        $db=DB_connect();
        $bOk=addBookmarkAndItsTags($db, "http://arturmarques.com", "Artur\'s site", array("personal", "artur"));
    }//testAddBookmarkAndItsTags

    //uninstaller();
    //createRequiredDBTables();
    //testAddBookmarkAndItsTags();
    

?>