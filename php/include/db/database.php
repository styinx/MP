<?php
    /*
     * A Logger-Class for the database class. Creates a file and fills with info messages.
     */
	class Log
	{
		private $path				= "";
		private $filename			= "";
		private $time				= null;
		private $handle				= null;
		private $logMode			= "";
        private $log_type           = 0;

		function __construct($path, $file = "DBlog.txt", $logMode = "w+")
		{
			$this->path = $path;
			$this->filename = $file;
			$this->logMode = $logMode;
			if($this->path == "")
			    $this->path = dirname(__FILE__);
			$this->open();
		}

		function open()
		{
			$success = false;
            if($this->log_type > 0)
            {
			    $this->handle = fopen($this->path . "/" . $this->filename, $this->logMode);
			    $this->log("SESSION START");

			    if($this->handle != null)
			    {
				    $success = true;
			    }
            }
			return $success;
		}

		function close()
		{
			$success = false;
			$success = fclose($this->handle);
			return $success;
		}

		function isOpen()
		{
			$success = false;
			if($this->handle != null)
			{
				$success = true;
			}
			return $success;
		}

		function log($msg)
		{
			$success = false;
            if($this->log_type >= 1)
            {
			    if($this->isOpen())
			    {
				    $this->time = time();
				    fwrite($this->handle, date("d.m.Y H:i:s : ") . "\t[INFO]\t" . $msg . "\r\n");
				    $success = true;
			    }
            }
			return $success;
		}

		function war($msg)
		{
			$success = false;
            if($this->log_type >= 2)
            {
			    if($this->isOpen())
			    {
				    $this->time = time();
				    fwrite($this->handle, date("d.m.Y H:i:s : ") . "\t[WARNING]\t" . $msg . "\r\n");
				    $success = true;
			    }
            }
			return $success;
		}

		function err($msg)
		{
			$success = false;
            if($this->log_type >= 3)
            {
			    if($this->isOpen())
			    {
				    $this->time = time();
				    fwrite($this->handle, date("d.m.Y H:i:s : ") . "\t[ERROR]\t" . $msg . "\r\n");
				    $success = true;
			    }
            }
			return $success;
		}
	}

    /*
     * A PHP Wrapper-Class to perform actions on a database. All common functions are supported
     *
     * DB -> TB -> FD -> ENT
     * Database -> Table -> Field -> Entry
     *
     */
	class DB
	{
		private $db_server 			= "";
		private $db_user 			= "";
		private $db_pw 				= "";
		private $db_name 			= "";

		private $db_con 			= null;
		private $db_db 				= "";
		private $db_tb 				= "";
		private $db_query 			= "";
		private $db_query_result 	= null;

		private $log				= null;
		private $dailyLog			= true;

		private $MYSQL_SECURE_QUERY = 0;

		/*
		 * Creates a new Databasehandler to make interactions with a database
		 * $server 	= Servername
		 * $user 	= Username
		 * $pw		= Passwort
		 * $name	= Name der Datenbank
		 */
		public function __construct($server, $user, $pw, $db = "", $logMode = "w+")
		{
			$this->db_server 	= $server;
			$this->db_user 		= $user;
			$this->db_pw 		= $pw;
			$this->db_db		= $db;

			if($this->dailyLog)
			{
				$logMode = "a+";
			}

			$this->log			= new Log(dirname(__file__) . "/..", "DBlog_" . date("Y-m-d", time()) . ".txt", $logMode);

			$this->connect($this->db_server, $this->db_user, $this->db_pw, $this->db_db);
		}

		/*
		 * Connects with a database
		 * @param $server	= Serverhost
		 * @param $user		= Username
		 * @param $pw		= Password
		 * @param $db		= Database
		 * @return success
		 */
		private function connect($server, $user, $pw, $db)
		{
			$success = false;

			if(!$this->isConnected())
			{
				$this->db_con = mysqli_connect($server, $user, $pw, $db);
				if($this->isConnected())
				{
					$success = true;
					$this->log->log("Connected with database.");
				}
				else
				{
					$this->log->err("No connection possible with database.");
				}
			}
			return $success;
		}

		public function getLog()
		{
			return $this->log;
		}

		/*
		 * Returns the current connection
		 * @return connection
		 */
		private function getCon()
		{
			$success = null;

			if($this->isConnected())
			{
				$result = $this->db_con;
			}
			else
				$this->log->err("No connection to database.");
			return $result;
		}

		/*
		 * Checks the connection with the database
		 * @return success
		 */
		public function isConnected()
		{
			$success = false;

			if($this->db_con != null)
			{
				$success = true;
			}
			return $success;
		}

		/*
		 * Creates a new database query
		 * @param $info	= query
		 * @return queryresult
		 */
		public function query($info)
		{
			$query = null;

			if($this->isConnected())
			{
				$this->db_query = $info;
				if($this->MYSQL_SECURE_QUERY)
					$this->db_query_result = mysqli_prepare($this->db_con, $this->db_query);			// TODO
				else
					$this->db_query_result = mysqli_query($this->db_con, $this->db_query);
				$query = $this->db_query_result;
				$this->log->log("Query request: " . $info);
			}
			else
				$this->log->err("Query could not be executed. No connection to database.");
			return $query;
		}

		/*
		 * Performs a databse query and returns the result as assoziative array
		 * @param $info = query
		 * @return queryresult as array
		 */
		public function queryAssoc($info)
		{
			$result = array();

			$query = $this->query($info);
			while($row = mysqli_fetch_assoc($query))
			{
				array_push($result, $row);
			}

			return $result;
		}

		/*
		 *	Creates a database query and returns first entry from query
		 *	@param $info = query
		 *	@param $assoc = if result is associative or numerical
		 *	@return	database entry
		 */
		public function singleQueryResult($info, $assoc = true)
		{
			$return = null;
			if(($query = $this->query($info)) != null)
			{
				if($assoc)
					$return = mysqli_fetch_assoc($query);
				else
					$return = mysqli_fetch_array($query);
			}
			return $return;
		}

		/*
		 * Returns the latest SQL query
		 * @return query
		 */
		public function getQuery()
		{
			return $this->db_query;
		}

		/*
		 * Returns the latest SQL query result
		 * @return query result
		 */
		public function getQueryResult()
		{
			return $this->db_query_result;
		}

		/*
		 * Returns the name of the selected database
		 * @return name of the selected database
		 */
		public function getDBName()
		{
			return $this->db_name;
		}

		/*
		 * Returns the name of the selected databasetable
		 * @return name of the selected table
		 */
		public function getTBName()
		{
			return $this->db_tb;
		}

		/*
		 * Creates a new database if it does not exist
		 * @param $name 	= name of the database
		 */
		public function createDatabase($name)
		{
			$success = false;

			if(!(@mysqli_select_db($this->db_con, $name)))
			{
                if($this->query("CREATE DATABASE ".$name) != null)
				{
					$success = true;
					$this->log->log("Database $name was created.");
					$this->selectDatabase($name);
				}
				else
				{
					$this->log->war("Database '$name' already exists. (No database could be created)");
				}
			}
			return $success;
		}

		/*
		 * Selects a database
		 * @param $name		= name of the database
		 * @return success
		 */
		public function selectDatabase($name)
		{
			$success = false;

			if($this->db_db = mysqli_select_db($this->db_con, $name) > 0)
			{
				$this->db_name = $name;
				if(mysqli_num_rows($this->query("SHOW TABLES")) > 0)
				{
					$tb = mysqli_fetch_array($this->db_query_result);
					$this->db_tb = $tb[0];
				}
				$this->log->log("Select Database '$name'");
				$success = true;
			}
			else
				$this->log->war("Database '$name' does not exist. (No database could be selected)");
			return $success;
		}

		/*
		 * Deletes a database if it does exist
		 * @param $name 	= name of the database
		 * @return success
		 */
		public function dropDatabase($name)
		{
			$success = false;

			if(mysqli_select_db($this->db_con, $name))
			{
				if(($this->query("DROP DATABASE " . $name)) != null)
				{
                    $success = true;
                    $this->log->log("Database '$name' was deleted.");
				}
			}
			else
				$this->log->war("No database exists with the name '$name''.");
			return $success;
		}

		/*
		 * Checks if database does exist
		 * @return success
		 */
		public function existsDatabase($name)
		{
			$success = false;

			if(@mysqli_select_db($this->db_con, $name))
			{
				$success = true;
			}
			return $success;
		}

		/*
		 * Creates a new table if it doesnt exist
		 * @param $name		 	= name of the table
		 * @param $fieldName	= array of field identifiers
		 * @param $type			= array of field types
		 * @param $null			= array of
		 * @param $key			= array of
		 * @param $default		= array of field default value
		 * @param $extra		= array of
		 * @return success
		*/
		public function createTable($name, $fieldName, $type, $null, $key, $default, $extra)
		{
			$success = false;

			if(!(@mysqli_num_rows($this->query("SHOW TABLES LIKE '".$name."'"))==1))
			{
				$statement = "CREATE TABLE ".$name." (";

				for($i = 0; $i < count($fieldName); $i++)
				{
					@$statement .= $fieldName[$i]." ".$type[$i]." ".$null[$i]." ".$extra[$i];

					if(!empty($default[$i]) && $default[$i] != "")
						$statement .= " DEFAULT '".$default[$i]."'";
					if($key == "UNIQUE INDEX")
						$statement .= " UNIQUE INDEX";

					if($i != count($fieldName)-1)
						$statement .= ", ";
				}
				for($i = 0; $i < count($fieldName); ++$i)
				{
					if($key[$i] == "PRIMARY KEY")
						$statement .= ", ".$key[$i]."(".$fieldName[$i].")";
				}
				$statement .= ");";
				if($this->query($statement) != null)
				{
				    $success = true;
				    $this->log->log("Table $name was created.");
				}
				$this->db_tb = $name;
			}
			else
			{
			    $this->log->war("Table '$name' already exists. (No table was created)");
			}
			return $success;
		}

        /*
		 * Selects a databasetable
		 * @param $name   = Name der Tabelle
		 */
		public function selectTable($name)
		{
		    $success = false;
		    $query = $this->query("SHOW TABLES LIKE '" . $name . "'");
            if(@mysqli_num_rows($query) > 0)
            {
                $this->db_tb = $name;
                $success = true;
                $this->log->log("Select table '$name'.");
            }
            else
            {
                $this->log->war("Table '$name' does not exist. (No table was selected)");
            }
            return $success;
		}

		/*
		 * Renames a table if it does exist
		 * @param $name		= table name
		 * @param $newName 	= new table name
		 */
		public function renameTable($name, $newName)
		{
			$success = false;
            if($this->existsTable($name))
            {
            	$sql = "ALTER TABLE " . $name . " RENAME " . $newName;
            	if($this->query($sql) != null)
				{
					$this->db_tb = $newName;
					$success = true;
					$this->log->log("Rename table '$name' to '$newName'.");
				}
            }
            else
            {
                $this->log->war("Table '$name' does not exist. (No table was renamed)");
            }
            return $success;
		}

		/*
		 * Deletes a table if it does exist
		 * @param $name 	= Name der Tabelle
		 * @return success
		 */
		public function dropTable($name)
		{
			$success = false;
			if(@mysqli_num_rows($this->query("SHOW TABLES LIKE '".$name."'"))==1)
			{
				if($this->query("DROP TABLE ".$name) != null)
				{
                    $success = true;
                    $this->log->log("Table $name was deleted.");
				}
			}
			else
				$this->log->war("No table with the name $name found. (No table was deleted)");
			return $success;
		}

		/*
		 * Checks if a database table does exist
		 * @param $name	= $table name
		 */
		public function existsTable($name)
		{
			$success = false;

			if(@mysqli_num_rows($this->query("SHOW TABLES LIKE '".$name."'"))==1)
				$success = true;

			return $success;
		}

		/*
		 * Adds columns to an existing table if it does exist
		 * @param $table	 	= name of the table
		 * @param $names		= array of field identifiers
		 * @param $type			= array of field types
		 * @param $null			= array of
		 * @param $key			= array of
		 * @param $default		= array of field default value
		 * @param $extra		= array of
		 * @param $pos			= array of
		 * @return success
		*/
		public function insertFields($table, $names, $types, $null, $key, $default, $extra, $pos)
		{
			$success = false;

			if($this->existsTable($table))
            {
                $sql = "ALTER TABLE " . $table;

                for($i = 0; $i < count($names); ++$i)
                {
                    $sql .= " ADD COLUMN " . $names[$i] . " " . $types[$i] . " " . $null[$i]
                        . " " . $default[$i] . " " . $extra[$i] . " " . @$pos[$i];
                }

                if($this->query($sql) != null)
                    $success = true;
            }
			return $success;
		}

		/*
		 * Modifies a existing table if it does exist
		 * @param $table	 	= name of the table
		 * @param $names		= array of field identifiers
		 * @param $newNames		= array of new field identifiers
		 * @param $type			= array of field types
		 * @param $null			= array of
		 * @param $key			= array of
		 * @param $default		= array of field default value
		 * @param $extra		= array of
		 * @param $pos			= array of
		 * @return success
		*/
		public function modifyFields($table, $names, $newNames, $types, $null, $key, $default, $extra, $pos)
		{
			$success = false;

            if($this->existsTable($table))
            {
                $sql = "ALTER TABLE " . $table;

                for($i = 0; $i < count($names); ++$i)
                {
                    $namesCombo = "";
                    if($newNames[$i] == "")
                        $namesCombo = $names[$i] . " " . $names[$i];
                    else
                        $namesCombo = $names[$i] . " " . $newNames[$i];

                    $sql .= " CHANGE COLUMN " . $namesCombo . " " . $types[$i] . " " . $null[$i]
                        . " " . $default[$i] . " " . $extra[$i] . " " . $pos[$i];
                }
            }
			return $success;
		}

		/*
		 * Deletes a column in an existing table
		 * @param $table
		 * @param $cols
		 */
		public function dropFields($table, $cols)
		{
			$success = false;

            if($this->existsTable($table))
            {
                $sql = "ALTER TABLE " . $table;

                foreach($cols as $col)
                {
                    $sql .= " DROP COLUMN " . $col;
                }

                if($this->query($sql) != null)
                    $success = true;
            }
			return $success;
		}

		/*
		 * Creates a new entry in a databsetable
		 * $keys	= array of field parameters
		 * $values	= array of field values
		 */
		public function insertEntry($table, $keys, $values)
		{
			$success = false;
			$query = null;

			$statementTable = $this->db_tb;
			$statementKey 	= "";
			$statementValue = "";

            if($this->existsTable($table))
            {
                if($table != "")
                {
                    $statementTable = $table;
                }
                for($i = 0; $i < count($keys); $i++)
                {
                    if(!($i == count($keys) - 1))
                    {
                        $statementKey .= $keys[$i] . ", ";
                    } else
                    {
                        $statementKey .= $keys[$i];
                    }
                }
                for($i = 0; $i < count($values); $i++)
                {
                    if(!($i == count($keys) - 1))
                    {
                        $statementValue .= "'" . $values[$i] . "', ";
                    } else
                    {
                        $statementValue .= "'" . $values[$i] . "'";
                    }
                }
                $query = $this->query("INSERT INTO " . $statementTable . " (" . $statementKey . ") "
                    . "VALUES (" . $statementValue . ")");
            }
			if($query != null)
				$success = true;
			return $success;
		}

		/*
		 * Returns an associative array from a query result
		 * $table 	= the name of the table to pull information
		 * $fields	= the name of the fields
		 * $key		= the key of the field
		 * $value	= the value of the field
		 * $like	=
		 * $limit	=
		 */
		public function selectEntry($table, $fields, $key, $value)
		{
			$entry 			= [];
			$selection 		= "";
			$identification = "";

            if($this->existsTable($table))
            {
                if(empty($fields))
                {
                    $selection = "*";
                } else
                {
                    for($i = 0; $i < count($fields); ++$i)
                    {
                        if(!($i == count($fields) - 1))
                            $selection .= $fields[$i] . ", ";
                        else
                            $selection .= $fields[$i];
                    }
                }
                if(!empty($key) and !empty($value))
                {
                    $identification .= $key . " = '" . $value . "'";
                } else
                {
                    $identification = "1";
                }
                if($this->query("SELECT " . $selection . " FROM " . $table . " WHERE " . $identification))
                {
                    $entry = mysqli_fetch_assoc($this->db_query_result);
                }
            }
			return $entry;
		}

		/*
		 *	Modifies a database entry
		 * 	$table	= database table
		 *	$keys	= key of the entry
		 *	$values	= value of the entry
		 *	$index	= primary key
		 *	$index 	= primary number
		 */
		public function updateEntry($table, $keys, $values, $index, $indexValue)
		{
			$success = false;
			$statementTable = $this->db_tb;
			if($table != "")
			{
				$statementTable = $table;
			}
            if($this->existsTable($table))
            {
                if(count($keys) == count($values))
                {
                    $statement = "";
                    for($i = 0; $i < count($keys); $i++)
                    {
                        if(!($i == count($keys) - 1))
                        {
                            $statement .= $keys[$i] . "='" . $values[$i] . "', ";
                        } else
                        {
                            $statement .= $keys[$i] . "='" . $values[$i] . "'";
                        }
                    }
                    $query = $this->query("UPDATE " . $statementTable . " SET " . $statement
                        . " WHERE " . $index . "='" . $indexValue . "'");
                    if($query != null)
                        $success = true;
                    return $success;
                }
            }
		}

		/*
		 *	Deletes a database entry
		 *	$index	= primary Key
		 *	$index 	= primary number
		 */
		public function deleteEntry($table, $index, $indexValue)
		{
			$success = false;
			$query = null;
            if($this->existsTable($table))
            {
                $query = $this->query("DELETE FROM " . $this->db_tb . " WHERE " . $index . "='" . $indexValue . "'");
            }
			if($query != null)
				$success = true;
			return $success;
		}

		/*
		 * Checks if a table entry with the given key value pair exists
		 * $key		= key of the entry
		 * $value	= value of the entry
		 */
		public function existsEntry($table, $key, $value)
		{
			$success = false;
            if($this->existsTable($table))
            {
                if(@mysqli_num_rows($this->query("SELECT " . $key . " FROM " . $this->db_tb
                        . " WHERE " . $key . "='" . $value . "'")) == 1)
                {
                    $success = true;
                }
            }
			return $success;
		}

		/*
		 * Saves a database table to a file
		 * $filename 	= the filename to which the contents will be saved
		 * $table		= the table to pull information from
		 */
		public function saveTable($filename, $table)
		{
		    $success = false;
		    $filename = str_replace("\\", "/", $filename);
			if(file_exists(substr($filename, 0, strrpos($filename, '/'))))
			{
                $this->selectDatabase($this->db_name);
			    if($this->selectTable($table))
                {
                    @unlink($filename);
                    $this->query("SELECT * INTO OUTFILE '$filename' FIELDS TERMINATED BY '%'
                                  OPTIONALLY ENCLOSED BY '#' LINES TERMINATED BY '$\n' FROM $table");

                    $tableQuery = $this->query("SHOW COLUMNS FROM " . $table);

                    $names = array();
                    $types = array();
                    $null = array();
                    $key = array();
                    $default = array();
                    $extra = array();
                    while($columns = mysqli_fetch_array($tableQuery))
                    {
                        array_push($names, $columns[0]);
                        array_push($types, $columns[1]);

                        if($columns[2] == "NO")
                            array_push($null, "NOT NULL");
                        else
                            array_push($null, "");

                        if($columns[3] == "PRI")
                            array_push($key, "PRIMARY KEY");
                        else if($columns[3] == "UNI")
                            array_push($key, "UNIQUE INDEX");
                        else
                            array_push($key, "");

                        array_push($default, $columns[4]);
                        array_push($extra, $columns[5]);
                    }
                    $prepend = $table . "\n";
                    $prepend .= implode(",", $names) . "\n";
                    $prepend .= implode(",", $types) . "\n";
                    $prepend .= implode(",", $null) . "\n";
                    $prepend .= implode(",", $key) . "\n";
                    $prepend .= implode(",", $default) . "\n";
                    $prepend .= implode(",", $extra) . "\n";

                    $content = file_get_contents($filename);
                    file_put_contents($filename, $prepend . $content);
                    $this->log->log("Saved table $table.");
				    $success = true;
                }
			}
			else
			{
			    mkdir(substr($filename, 0, strrpos($filename, '/')), 0777, true);
				$this->saveTable($filename, $table);
			}
			return $success;
		}

		/*
		 * Loads a database table from a file into the database
		 * $filename	=   the path and filename to the sql file
		 * $table		=   the name of the table that will be created
		                    if not used,...
		 */
		public function loadTable($filename, $table = "")
		{
		    $success = false;
		    $filename = str_replace("\\", "/", $filename);
			if($this->existsTable($table))
            {
                $this->query("LOAD DATA INFILE '$filename' INTO TABLE $table FIELDS TERMINATED BY '%'
                              OPTIONALLY ENCLOSED BY '#' LINES TERMINATED BY '$\n' IGNORE 7 LINES");
                $this->log->log("Loaded table $table.");
                $success = true;
            }
            else
            {
                $handle = fopen($filename, "r");
                $tableName = str_replace("\n", "", fgets($handle));
                $names = explode(",", str_replace("\n", "", fgets($handle)));
                $types = explode(",", str_replace("\n", "", fgets($handle)));
                $null = explode(",", str_replace("\n", "", fgets($handle)));
                $key = explode(",", str_replace("\n", "", fgets($handle)));
                $default = explode(",", str_replace("\n", "", fgets($handle)));
                $extra = explode(",", str_replace("\n", "", fgets($handle)));
                fclose($handle);

                $this->createTable($tableName, $names, $types, $null, $key, $default, $extra);
                $this->loadTable($filename, $table);
            }
            return $success;
		}

		/*
		 * Saves all tables in a database to the given path
		 * $path	= the path where the tables files will be stored
		 * $name	= the name of the database
		 */
		public function saveDatabase($path, $name)
		{
			$success = false;
			if($path == "")
			    $path = dirname(__FILE__) . "/backup/";
			if($this->selectDatabase($name) > 0)
			{
				$query = $this->query("SHOW TABLES");
				while($table = mysqli_fetch_array($query))
				{
					$this->saveTable($path . "/" . $table[0] . ".sql", $table[0]);
				}
				$success = true;
				$this->log->log("Saved database $name to $path.");
			}
			return $success;
		}

		/*
		 * Loads all database tables from the given path into the database
		 * $path	= the path to the sql files
		 * $name	= the name of the database where contents will be loaded, will be created if it does not exist
		 */
		public function loadDatabase($path, $name)
		{
			$success = false;
			if($path == "")
			    $path = dirname(__FILE__) . "/backup/";
			if($this->existsDatabase($name))
			{
				if($handle = opendir($path))
				{
					while(($entry = readdir($handle)) !== false)
					{
						if(!is_dir($path . "/" . $entry))
							$this->loadTable($path . "/" . $entry, substr($entry, 0, strpos($entry, ".sql")));
					}
					$success = true;
					$this->log->log("Loaded database $name from $path.");
				}
			}
			else
			{
				$this->createDatabase($name);
				$this->loadDatabase($path, $name);
			}
			return $success;
		}

		/*
		 * Gibt alle Datenbanken aus
		 */
		public function showLocalhost()
		{
			$echo = "";
			$query = $this->query("SHOW DATABASES");

			$echo .= "<table border='0' cellspacing='0'>";

			while($db = mysqli_fetch_array($query))
			{
				$this->selectDatabase($db[0]);
				$echo .= $this->showDatabase($db[0]);
			}
			$echo .= "</table>";
			return $echo;
		}

		/*
		 * Gibt alle Datenbanken mit Werten aus
		 */
		public function showLocalhostValue()
		{
			$echo = "";
			$query = $this->query("SHOW DATABASES");

			$echo .= "<table border='0' cellspacing='0'>";

			while($db = mysqli_fetch_array($query))
			{
				$this->selectDatabase($db[0]);
				$echo .= $this->showDatabaseValue($db[0]);
			}
			$echo .= "</table>";
			return $echo;
		}

		/*
		 * Gibt die ausgewählte Datenbank mit Tabellen und Feldern aus
		 */
		public function showDatabase($name)
		{
			$echo = "";
			$result = $this->selectDatabase($name);
			if(!empty($result))
			{
				$echo .= "<table border='0' cellspacing='0'>"
							."<tr>"
								."<td><h1>Datenbank:&nbsp;&nbsp;</h1></td><td align='right'><h1>".$this->db_name ."</h1></td>"
							."</tr>"
						."</table>";

				$query = $this->query("SHOW TABLES");

				if(@mysqli_num_rows($query))
				{
					while($tables = mysqli_fetch_array($query))
					{
						$tableQuery = $this->query("SHOW COLUMNS FROM ".$tables[0]);

						if(mysqli_num_rows($tableQuery) > 0)
						{
							$echo .= "<table border='0' cellspacing='0'>"
										."<tr>"
											."<td><h2>Tabelle:&nbsp;&nbsp;</h2></td><td align='right'><h2>".$tables[0]."</h2></td>"
										."</tr>"
									."</table>";

							$echo .= "<table border='1' cellspacing='0'>"
									."<tr>"
										."<td><b>Name</b></td>"
										."<td><b>Typ</b></td>"
										."<td><b>NULL</b></td>"
										."<td><b>Primary</b></td>"
										."<td><b>Default</b></td>"
										."<td><b>Extra</b></td>"
									."</tr>";

							while($columns = mysqli_fetch_array($tableQuery))
							{
								$echo .= "<tr>"
											."<td>".$columns[0]."</td>"
											."<td>".$columns[1]."</td>"
											."<td>".$columns[2]."</td>"
											."<td>".$columns[3]."</td>"
											."<td>".$columns[4]."</td>"
											."<td>".$columns[5]."</td>"
										."</tr>";
							}
							$echo .= "</table>";
						}
					}
				}
			}
			return $echo;
		}

		/*
		 *	Gibt die ausgewählte Datenbank mit Tabellen und Werten aus
		 */
		public function showDatabaseValue($name)
		{
			$echo = "";
			$result = $this->selectDatabase($name);
			if($result)
			{
				$echo .= "<table border='0' cellspacing='0'>"
							."<tr>"
								."<td><h1>Datenbank:&nbsp;&nbsp;</h1></td><td align='right'><h1>".$this->db_name ."</h1></td>"
							."</tr>"
						."</table>";

				$query = $this->query("SHOW TABLES");

				if(@mysqli_num_rows($query))
				{
					while($tables = mysqli_fetch_array($query))
					{
						$tableQuery = $this->query("SELECT * FROM ".$tables[0]);

						if(mysqli_num_rows($tableQuery) > 0)
						{
							$echo .= "<table border='0'>"
										."<tr>"
											."<td><h2>Tabelle:&nbsp;&nbsp;</h2></td><td align='right'><h2>".$tables[0]."</h2></td>"
										."</tr>"
									."</table>";

							$echo .= $this->showTable($tables[0]);
						}
					}
				}
			}
			return $echo;
		}

		public function showTable($table)
		{
			$echo = "";
			$echo .= "<table border='1' cellspacing='0'>";
			if(!empty($this->db_name))
			{
				$cols = $this->query("SHOW COLUMNS FROM ".$table);
				$datas = $this->query("SELECT * FROM ".$table);


				if(($datanum = mysqli_num_rows($cols)) > 0 && (mysqli_num_rows($datas)) > 0)
				{
					$echo .= "<tr>";
					while($col = mysqli_fetch_array($cols))
						$echo .= "<td><b>".$col[0]."</b></td>";
					$echo .= "</tr>";

					while($data = mysqli_fetch_array($datas))
					{
						$echo .= "<tr>";
						for($i = 0; $i < $datanum; $i++)
						{
							$echo .= "<td>".$data[$i]."</td>";
						}
						$echo .= "</tr>";
					}
				}
			}
			$echo .= "</table>";
			return $echo;
		}
	}
?>

