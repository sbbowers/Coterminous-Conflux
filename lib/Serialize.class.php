<?php
namespace C;

// The serialize class defines the base definition for encoding and 
// decoding data. Create your own serializers by extending this one 
// and redefining encode and decode
class Serialize
{
	function encode($data)
	{
		return serialize($data);
	}

	function decode($data)
	{
		return unserialize($data);
	}
}
