# Simple PHP Database Wrapper
The point of this MYSQL Database wrapper is to be super easy to setup and use.

Initialization:
```php
$_DATABASE = new database([
      "DBType"    => "mysql",
      "DBHost"    => "localhost",
      "DBName"    => "",
      "DBUser"    => "",
      "DBPassword"=> ""
  ]);
```
Methods:
  // A regular SQL Query. If $Single is set to true, it returns only the first array. $Execute is an associative array for setting prepared Statements.
  ```php
  query(string $SQL, bool $Single, array $Execute)  
  ```
  // Selects all $columns set in a 1D array $where columns are equal. Example query("users", ["firstName", "lastName"], ["id" => 1])
  ```php
  select(string $table, array $columns, array $where)
  ```
  // Inserts $column values into the set $table. If $ingnore is set to true, it won't error out on duplicate primary keys. Example insert("users", ["firstName" => "John", "lastName => "Smith"], false)
  ```php
  insert(string $table, array $columns, bool $ignore = false)
  ```
  // Updates $column values from the set $table where the conditions fit the associative $where array. Example update("users", ["firstName" => "Mark"], ["id" => 2])
  ```php
  update(string $table, array $columns, array $where = false)
  ```
  // Deletes rows from the set $table where the conditions fit the associative $where array. Example delete("users", ["id" => 2])
  ```php
  update(string $table, array $where = false)
  ```
  // Returns a random string that's unique to the set $column in the set $table.
  ```php
  createUniqueString(int $length, string $column, string $table)
  ```
  // Check if the $string exists in the set $column. Example exists("Mark", "firstName", "users")
  ```php
  exists(string $string, string $column, string $table)
  ```
  // Counts the rows from the $table where the conditions from $where match. Example count("users", ["lastName" => "Smith"])
  ```php
  count(string $table, array $where)
  ```
  // Returns the last inserted primary key
  ```php
  lastID()
  ```
