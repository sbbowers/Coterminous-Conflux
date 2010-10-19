<?php
echo '<'.$this['type'].' ';
foreach($this['attributes'] as $name => $value)
{
	echo $name.'="'.$value.'" ';
}
if(count($this['contents']))
{
	echo ">\n";
	foreach($this['contents'] as $renderable)
	{
		if(is_object($renderable))
			$renderable->render();
		else
			echo $renderable."\n";
	}
	echo "</{$this['type']}>\n";
}
else
	echo " />\n";
