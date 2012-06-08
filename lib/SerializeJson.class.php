<?php

// The serialize class defines the base definition for encoding and 
// decoding data. Create your own serializers by extending this one 
// and redefining encode and decode
class SerializeJson extends Serialize
{
	function encode($data)
	{
		return json_encode($data);
	}

	function decode($data)
	{
		return json_decode($data);
	}
}
