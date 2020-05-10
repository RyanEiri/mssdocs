<?php

class MySQLResult {
 	/**
  	* $mysql = Instance of MySQL providing database connection
  	* $query = Query resource
  	*/
 	private $sql;
 	private $query;

	/**
	* Returns the number of rows selected
	*/
	function size(){
		return $this->query->num_rows;
	}

 	/**
  	* MySQLResult constructor
  	*/
 	function __construct(&$sql, $query){
   		$this->sql = &$sql;
   		$this->query = $query;
 	}

 	/**
  	* Fetches a row from the result
  	*/
 	function fetch(){
   		if ($row = mysqli_fetch_array($this->query, MYSQLI_ASSOC)) {
     			return $row;
   		} else if ( $this->size() > 0 ) {
     			mysqli_data_seek($this->query, 0);
     			return false;
   		} else {
     			return false;
   		}
 	}

	function fetchArray(){
		for ($i = 0; $i < $this->size(); $i++) {
			$row[$i] = mysqli_fetch_array($this->query, MYSQLI_ASSOC);
		}
		if (isset($row)) {
			return $row;
		} else {
			return false;
		}
	}

	function getId(){
		return mysqli_insert_id();
	}

 	/**
  	* Checks for MySQL errors
  	*/
 	function isError(){
   		return $this->sql->isError();
 	}

	function queryErrorMessage(){
			return $this->sql->queryErrorMessage();
	}

}

class SQL {
	/**
	* $host = MySQL server hostname
	* $dbUser = MySQL username
	* $dbPass = MySQL user's password
	* $dbName = MySQL database to use
	* $dbConn = MySQL Resource link identifier stored here
	* $connectError = Stores error messages for connection errors
	*/
	public $host;
	private $dbUser;
	private $dbPass;
	public $dbName;
	public $dbConn;
	public $connectError;
	public $queryError;
	public $previousStatement;

	/**
	* MySQL constructor
	*/
	function __construct($host, $dbUser, $dbPass, $dbName){
		$this->host = $host;
		$this->dbUser = $dbUser;
		$this->dbPass = $dbPass;
		$this->dbName = $dbName;
		$this->connectToDb();
	}

	/**
	* Establishes connection to MySQL and selects a database
	*/
	function connectToDb(){
		// Make connection to MySQL server
		if (!$this->dbConn = @mysqli_connect($this->host,
		$this->dbUser, $this->dbPass, $this->dbName)) {
			trigger_error('Could not connect to server');
			$this->connectError = true;
		// Select database
		// Deprecated with change to mysqli interface
	  // Database is now selected upon connection
	  // See fourth variable in connection
//		} else if (!@mysql_select_db($this->dbName,$this->dbConn)) {
//			trigger_error('Could not select database');
//			$this->connectError = true;
		} else {
			@mysqli_set_charset($this->dbConn, 'utf8');
		}
	}

	/**
	* Checks for MySQL errors
	*/
	function isError(){
		if ($this->connectError) {
			return true;
		}
		if ($this->queryError) {
			return true;
		}
		$error = mysqli_error($this->dbConn);
		if (empty($error)) {
			return false;
		} else {
			return true;
		}
	}

	function queryErrorMessage(){
		if ($this->queryError) {
			return ('Query failed: ' . mysqli_error($this->dbConn)
                   		. ' SQL: ' . $this->previousStatement);
		}
	}

	/**
	* Returns an instance of MySQLResult to fetch rows with
	*/
	/*
	* The $param is to pass in an array of parameters to bind to the sql statement  - The two array values are
	*
	* 'type' and 'value'  The array should look like
	*
	* $params[] = Array( 'type' => 's', 'value' => 'my value' );
	*
	* Currently this addition was only made for INSERT statements
	*
	*/
	public function query($statement, $params = NULL){ // default for $params set to NULL
		// Check for parameters to form a prepared statement


			// prepare statement
			$conn = $this->dbConn;
			$stmt = $conn->prepare($statement);
			if ($stmt === false )
			{
					trigger_error('Wrong SQL: ' . $statement . ' Error: ' . $conn->errno . ' ' . $conn->error, E_USER_ERROR);
			}

			if(is_array($params)) {
			//initialize param variables
			$a_params = array();
			$param_type = '';

			//build arrays for params
			for( $i = 0; $i < count($params); $i++ )
			{
				//add param types: s = string, i = integer, d = double,  b = blob
				$param_type .= $params[$i]['type'];

				//add parameter
				$a_params[$i] = &$params[$i]['value'];
			}

			//add parameter type to beginning of the array
			array_unshift($a_params, $param_type);

			//use call_user_func_array, as $stmt->bind_param('s', $param); does not accept params array
			call_user_func_array(array($stmt, 'bind_param'), $a_params);
			}
			//execute statement
			if (!$exec = $stmt->execute()) {
				trigger_error('Query failed: ' . mysqli_error($conn)
										 . ' SQL: ' . $statement);
				$this->queryError = true;
				$this->previousStatement = $statement;
				return new MySQLResult($this, $exec);
			} else {
				if($result = $stmt->get_result()){
					return new MySQLResult($this, $result);
				} else {
					return new MySQLResult($this, $exec);
				}
			}

		/*} else {
			if (!$queryResource = mysqli_query($this->dbConn, $statement)) {
						trigger_error('Query failed: ' . mysqli_error($this->dbConn)
												. ' SQL: ' . $statement);
						$this->queryError = true;
						$this->previousStatement = $statement;
						return new MySQLResult($this, $queryResource);
			} else {
						return new MySQLResult($this, $queryResource);
			}
		}*/

	}

}

?>
