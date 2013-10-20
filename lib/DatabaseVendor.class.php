<?php
namespace C;

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
	public abstract function format($value, $type);

	// Builds the sql to define all columns of all tables in a database
	// format of: (schema, table, column, required, type, default, text_length)
	public abstract function get_columns();

	// Builds the sql to determine the primary key of all tables
	// format of: (schema, table, column)
	public abstract function pkey_sql();

	// Builds the sql to determine all foreign keys
	// format of: (name, schema, table, column, ref_schema, ref_table, ref_column)
	public abstract function fkey_sql();

	// Gets the sql clause to retreive the last value from a sequence
	// Used after an insert to propagate the model with the primary keys
	public abstract function get_last_column_default($table_name, $column_name);

}

