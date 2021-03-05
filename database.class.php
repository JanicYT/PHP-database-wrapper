<?php
class database {
    public      $RowCount;

    protected   $Connection;

    function __construct(array $connectDetails, bool $showErrors = false) {
        try {
            $this->Connection = new PDO($connectDetails["DBType"].":host=".$connectDetails["DBHost"].";dbname=".$connectDetails["DBName"].";charset=utf8", $connectDetails["DBUser"], $connectDetails["DBPassword"],
                    [
                        PDO::ATTR_EMULATE_PREPARES      => false,
                        PDO::ATTR_PERSISTENT            => true,
                        PDO::MYSQL_ATTR_INIT_COMMAND    => "SET NAMES utf8"
                    ]);

            if ($showErrors) $this->Connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return true;
        } catch (PDOException $e) {
            header($_SERVER['SERVER_PROTOCOL'] . ' Database Unavailable', true, 503);
            if ($showErrors) die("<strong>Database Error: </strong>". $e);
            else             die("<strong>Database Error:</strong>");
        }
    }
    
    // Executes a SQL query
    public function query(string $SQL, bool $Single = false, array $Execute = []) {
        $Query = $this->Connection->prepare($SQL);
        $Query->execute($Execute);

        $this->RowCount = $Query->rowCount();

        // If it's a SELECT statement it returns an array. Otherwise it returns a boolean of whether or not it was updated successfully
        if (strpos($SQL, "SELECT") !== false) {
            if ($this->RowCount == 0)   return [];
            elseif ($Single)            return @$Query->fetch(PDO::FETCH_ASSOC);
            else                        return @$Query->fetchAll(PDO::FETCH_ASSOC);
        } else {
            if ($this->RowCount > 0)    return true;
            else                        return false;
        }
    }

    // Selects columns according to the set where conditions
    public function select(string $table, array $columns, array $where) : array {
        // Get columns
        $selectSQL      = "";
        $Values_Array   = [];
        foreach ($columns as $column) {
            $selectSQL .= "$column,";
        }
        $selectSQL      = rtrim($selectSQL, ",");

        // Generate where conditions
        $whereSQL = "";
        if ($where) {
            $whereSQL .= "WHERE";
            foreach ($where as $column => $condition) {
                $whereSQL                   .= " $column = :$column AND";
                $Values_Array[":".$column]   = $condition;
            }
            $whereSQL = rtrim($whereSQL, "AND");
        }

        // Execute query
        if ($results = $this->query("SELECT $selectSQL FROM $table $whereSQL", false, $Values_Array)) {
            return $results;
        }
        return [];
    }
    
    // Inserts a value into the database
    // Special column values:
        // 0 = NOW()
        // 1 = NULL
    public function insert(string $table, array $columns, bool $ignore = false) : bool {
        // Get columns
        $Columns        = "";
        $valuesPrepare  = "";
        $Values_Array   = [];
        foreach ($columns as $column => $value) {
            $Columns .= $column.",";
            if ($value !== 0 && $value !== 1) {
                $valuesPrepare              .= ":".$column.",";
                $Values_Array[":".$column]   = $value;
            } elseif ($value === 0)  $valuesPrepare .= "NOW(),";
              elseif ($value === 1)  $valuesPrepare .= "NULL,";
        }
        $Columns            = rtrim($Columns, ",");
        $valuesPrepare      = rtrim($valuesPrepare, ",");

		if (!$ignore) 	$ingoreSQL = "";
		else 		 	$ingoreSQL = "IGNORE";

        // Execute query
        if ($this->query("INSERT $ingoreSQL INTO $table ($Columns) VALUES ($valuesPrepare)", false, $Values_Array))  	return true;
        else                                                                                                        return false;
    }
    
    // Updates columns in the set table
    // Special column values:
        // 0 = NOW()
        // 1 = NULL
    public function update(string $table, array $columns, array $where) : bool {
        // Get columns
        $valuesPrepare  = "";
        $Values_Array   = [];
        foreach ($columns as $column => $value) {
            if ($value !== null) {
                if ($value !== 0 && $value !== 1) {
                    $valuesPrepare              .= "$column = :".$column.",";
                    $Values_Array[":".$column]   = $value;
                } elseif ($value === 1)  $valuesPrepare .= "$column = NOW(),";
                  elseif ($value === 2)  $valuesPrepare .= "$column = NULL,";
            }
        }
        $valuesPrepare      = rtrim($valuesPrepare, ",");
        
        // Generate where conditions
        $whereSQL = "";
        if ($where) {
            $whereSQL .= "WHERE";
            foreach ($where as $column => $condition) {
                $whereSQL                   .= " $column = :$column AND";
                $Values_Array[":".$column]   = $condition;
            }
            $whereSQL = rtrim($whereSQL, "AND");
        }
        
        // Execute query
        if ($this->query("UPDATE $table SET $valuesPrepare $whereSQL", false, $Values_Array))   return true;
        else                                                                                              return false;
    }

    // Deletes the row according to the set WHERE array
    public function delete(string $table, array $where) : bool {
        // Generate where conditions
        $whereSQL = "";
        if ($where) {
            $whereSQL .= "WHERE";
            foreach ($where as $column => $condition) {
                $whereSQL                   .= " $column = :$column AND";
                $Values_Array[":".$column]   = $condition;
            }
            $whereSQL = rtrim($whereSQL, "AND");
        }

        // Execute query
        if ($this->query("DELETE FROM $table $whereSQL", false, $Values_Array))   return true;
        else                                                                                return false;
    }
    
    // Creates a random string, checks if it's used in $column somewhere and if it is, generate another one and repeat.
    public function createUniqueString(int $length, string $column, string $table) : string {
    	$found = true;
    	while ($found) {
            if (!$this->exists($string = $this->randomString($length), $column, $table)) $found = false;
    	}
    	return $string;
    }
    
    // Check if string exists or not
    public function exists(string $string, string $column, string $table) : bool {
        $query = $this->query("SELECT EXISTS(SELECT * FROM $table WHERE $column = :STRING) as a", true, [":STRING" => $string]);

        if ($query["a"])    return true;
        else                return false;
    }
    
    public function count(string $table, array $where) : int {
        // Generate where conditions
        $Values_Array   = [];
        $whereSQL       = "";
        if ($where) {
            $whereSQL .= "WHERE";
            foreach ($where as $column => $condition) {
                $whereSQL                   .= " $column = :$column AND";
                $Values_Array[":".$column]   = $condition;
            }
            $whereSQL = rtrim($whereSQL, "AND");
        }

        return $this->query("SELECT count(*) as a FROM $table $whereSQL", true, $Values_Array)["a"];
    }

    // Returns the last inserted primary key
    public function lastID() : int {
        return $this->Connection->lastInsertId();
    }

    // Generates a random string. Used for the UniqueID method
    private function randomString(int $length) : string {
        $characters         = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
        $charactersLength   = strlen($characters);
        $randomString       = "";

        for ($i = 0; $i < $length; $i++) $randomString .= $characters[rand(0, $charactersLength - 1)];
        return $randomString;
    }
}