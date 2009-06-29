<?php
define('OWAD_CACHE_FILE', "cache/words.xml");

include("classes/class.owad.php");
include("classes/class.model.php");

// This is executed if an ajax request is sent.
if( isset( $_GET["wordid"] ) )
{ 	
	$word = Owad_Model::get_cached_word_by_id( intval( $_GET["wordid"] ) );
	
	if( $word == NULL )
	{
		// TODO: Adapt the array
		echo json_encode( array(
				"@attributes" => array(
				  	"wordid" => "",
				  	"date" => "",
				  	"content" => "" ),
				"alternative" => array( "", "", "" )
				));
	}
	else
		echo json_encode( $word );
	
	exit();
}

?>