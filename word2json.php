<?php
define('OWAD_CACHE_FILE', "cache/words.xml");

include("classes/class.owad.php");

// This is executed if an ajax request is sent.
if( isset( $_GET["wordid"] ) )
{ 	
	$set = Owad::get_word_by_id( intval( $_GET["wordid"] ) );
	
	if( $set == NULL )
	{
		echo json_encode( array(
				  "wordid" => "",
				  "date" => "",
				  "todays_word" => "",
				  "alternatives" => array( "", "", "" )
				  ));
	}
	else
		echo json_encode( $set );
	
	exit();
}

?>