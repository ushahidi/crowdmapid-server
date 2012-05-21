<?php

if(!defined('LOADED_SAFELY')) die('You cannot access this file directly.');

class MySQL
{

	private $link;

	public function __construct()
	{
		$this->link = @mysql_connect(CFG_SQL_HOST, CFG_SQL_USER, CFG_SQL_PASSWORD);
		if ( $this->link )
		{
			$db = @mysql_select_db(CFG_SQL_DATABASE, $this->link);
		}

		if ( !$this->link || !$db )
		{
			global $Response;
			$Response->Send(503, RESP_ERR, array(
				   'error' => 'Database is currently unavailable.'
			));
		}
	}

	public function Pull($statement)
	{
		$result = @mysql_query($statement, $this->link);
		if ( !$result )
		{
			global $Response;
			@file_put_contents('mysql_errors.txt', "\n{$statement}\n->" . mysql_error() . "\n", FILE_APPEND);
			$Response->Send(500, RESP_ERR, array(
				   'error' => 'An invalid database query was passed. An administrator has been notified.'
			));
		}

		$return_results = array();
		if ( mysql_num_rows($result) ) {
			while ( $row = @mysql_fetch_assoc($result) ) {
				$return_results[] = $row;
			}
		}

		if(substr($statement, -8) == 'LIMIT 1;' && count($return_results) == 1) {
			$return_results = $return_results[0];
		}

		@mysql_free_result($result);
		return $return_results;
	}

	public function Push($statement)
	{
		$result = @mysql_unbuffered_query($statement, $this->link);
		if ( !$result )
		{
			global $Response;
			@file_put_contents('mysql_errors.txt', "\n{$statement}\n->" . mysql_error() . "\n", FILE_APPEND);
			$Response->Send(500, RESP_ERR, array(
				   'error' => 'An invalid database query was passed. An administrator has been notified.'
			));
		}
		@mysql_free_result($result);

		if(substr($statement, 0, 6) == 'INSERT')
		{
			return @mysql_insert_id($this->link);
		}
		return true;
	}

	public function Clean($string)
	{
		// Prepare a string for use in a statement.
		if ( function_exists('mysql_real_escape_string') )
		{
			return mysql_real_escape_string($string);
		}
		elseif ( function_exists('mysql_escape_string') )
		{
			return mysql_escape_string($string);
		}
	}

}

$MySQL = new MySQL();
