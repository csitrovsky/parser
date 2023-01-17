<?php


namespace app\src;


use PDO;
use PDOException;


use function count;
use function implode;
use function ltrim;


/**
 * @property string              $_query
 * @property \PDO                $pdo
 * @property false               $result
 * @property false|\PDOStatement $statement
 */
class Database
{
    
    /**
     * @var string[]
     */
    private $dns = [
        'host=' . DATABASE_HOST,
        'dbname=' . DATABASE_NAME,
        'charset=' . 'UTF8',
    ];
    
    /**
     * @param $data
     *
     * @return void
     */
    public function insert($data): void
    {
        
        foreach ($data as $table => $datum) {
            $insert = "INSERT INTO `$table`" . ' SET ';
            $params = [];
            foreach ($datum as $item => $item) {
                $params[] = "`" . ltrim($item, ':') . "` = $item";
            }
            $params = implode(', ', $params);
            $insert .= $params . ';';
            $this->query($insert, $datum);
        }
    }
    
    /**
     * @param       $sql
     * @param array $params
     *
     * @return false|\PDOStatement
     */
    public function query($sql, array $params = [])
    {
        
        $this->statement = $this->connect()->prepare($sql);
        if ($this->statement && $this->statement->execute($params)) {
            return $this->statement;
        }
        
        return false;
    }
    
    /**
     * @return \PDO
     */
    private function connect(): PDO
    {
        
        $data = 'mysql:' . implode(';', $this->dns);
        try {
            $this->pdo = $pdo = new PDO($data, DATABASE_USERNAME, DATABASE_PASSWORD);
            
            $pdo->setAttribute(PDO::MYSQL_ATTR_FOUND_ROWS, true);
            $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            
            return $pdo;
        } catch (PDOException $e) {
            die((new Error())->output('Unable to connect', 945));
        }
    }
    
    /**
     * @param string $from
     * @param array  $params
     * @param bool   $distinct
     *
     * @return void
     */
    public function select(string $from = '', array $params = [], bool $distinct = false): void
    {
        
        $this->_query = 'SELECT ' . ((count($params) > 0) ? implode(
                ', ',
                $params
            ) : '*') . " FROM `$from`";
    }
    
    /**
     * @param array $params
     *
     * @return void
     */
    public function where(array $params = []): void
    {
        
        if (count($params)) {
            $this->_query .= ' WHERE ' . implode(' AND ', $params);
        }
    }
    
    /**
     * @param array $params
     *
     * @return void
     */
    public function order(array $params = []): void
    {
        
        if (count($params)) {
            $this->_query .= ' ORDER BY ' . implode(', ', $params);
        }
    }
    
    /**
     * @param array $params
     *
     * @return void
     */
    public function group(array $params = []): void
    {
        
        if (count($params)) {
            $this->_query .= ' GROUP BY ' . implode(', ', $params);
        }
    }
    
    /**
     * @param int $count
     *
     * @return void
     */
    public function limit(int $count = 1): void
    {
        
        $this->_query .= " LIMIT $count";
    }
    
    /**
     * @param string $join
     * @param string $table
     * @param array  $params
     *
     * @return void
     */
    public function join(string $join, string $table, array $params = []): void
    {
        
        $this->_query .= ' ' . strtoupper($join) . " JOIN `$table` ON (" . implode(' AND ', $params) . ')';
    }
    
    /**
     *
     */
    public function __destruct()
    {
        
        $this->pdo = null;
    }
    
    /**
     * @param int $count
     *
     * @return void
     */
    public function offset(int $count = 0): void
    {
        
        if ($count !== 0) {
            $this->_query .= " OFFSET $count";
        }
    }
}