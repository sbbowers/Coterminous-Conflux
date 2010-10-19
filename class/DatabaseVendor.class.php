<?php
/* 
	Abstract class to define a database vendor interface
	Implement this class for all of your vendors so you can 
	use the Db class with your database
*/

abstract class DatabaseVendor
{
	// Detects if a connection is busy
	public abstract function is_busy();

	// Method for formatting column values for a column type
	public static abstract function format($value, $type);

	// Builds the sql to list all of the tables in the database
	public abstract function list_tables_sql();

	// Builds the sql to define a table (column information)
	public abstract function table_sql($table_name);

	// Builds the sql to determine the primary key of a table
	public abstract function pkey_sql($table_name);

	// Builds the sql to determine what tables reference this one
	public abstract function reference_sql($table_name);

	// Gets the sql clause to retreive the last value from a sequence
	// Used after an insert to propagate the model with the primary keys
	public abstract function get_last_column_default($table_name, $column_name);

}

