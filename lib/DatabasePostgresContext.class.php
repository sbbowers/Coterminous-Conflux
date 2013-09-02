<?php
namespace C;

class DatabasePostgresContext
{
	private
		$result = null,
		$connection = null;

	public function __construct($result, $connection)
	{
		$this->result = $result;
		$this->connection = $connection;
	}

	public function result()
	{
		if(!is_resource($this->result))
		{
			$this->result = pg_get_result($this->connection);
			$error = pg_result_error($this->result);
			if($error)
				throw new Exception($error);
		}
		return $this->result;
		
	}

}
