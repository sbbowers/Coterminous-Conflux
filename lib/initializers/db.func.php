<?php
namespace C;

$config = Config::get('connection', 'available');
$dynamic_class_maker = new DynamicClassMaker('sub_functional_db_access');
foreach($config as $repo_name => $details)
{
	$dynamic_class_maker->add_values(['DB_LABEL' => $repo_name]);
	$dynamic_class_maker->make_class();
}
