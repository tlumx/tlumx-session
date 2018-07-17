<?php
/**
 * Tlumx (https://tlumx.com/)
 *
 * @author    Yaroslav Kharitonchuk <yarik.proger@gmail.com>
 * @link      https://github.com/tlumx/tlumx-servicecontainer
 * @copyright Copyright (c) 2016-2018 Yaroslav Kharitonchuk
 * @license   https://github.com/tlumx/tlumx-servicecontainer/blob/master/LICENSE  (MIT License)
 */
namespace Tlumx\Session\SaveHandler;

/**
 * PDO session save handler.
 *
 * Example SQLite table:
 * CREATE TABLE sessions (
 *      session_id VARCHAR(128) PRIMARY KEY,
 *      created INTEGER,
 *      last_impression INTEGER,
 *      data TEXT
 * )
 */
class PdoSaveHandler implements \SessionHandlerInterface
{
    /**
     * @var \PDO
     */
    protected $dbh;

    /**
     * @var string
     */
    protected $tableName;

    /**
     * @var array
     */
    protected $tableCols = [
        'session_id'        => 'session_id',
        'created'           => 'created',
        'last_impression'   => 'last_impression',
        'data'              => 'data'
    ];

    /**
     * Constructor
     *
     * @param \PDO $dbh
     * @param string $tableName
     * @param array $tableCols
     * @throws \InvalidArgumentException
     */
    public function __construct(\PDO $dbh, $tableName = 'sessions', array $tableCols = [])
    {
        $this->dbh = $dbh;

        if (!is_string($tableName) || empty($tableName)) {
                throw new \InvalidArgumentException('You must provide the "table" option for a PdoSessionHandler.');
        }
        $this->tableName = $tableName;

        $this->tableCols = array_merge($this->tableCols, $tableCols);
    }

    /**
     * Open Session
     *
     * @param string $savePath
     * @param tstring $sessionName
     */
    public function open($savePath, $sessionName)
    {
        return true;
    }

    /**
     * Close Session
     */
    public function close()
    {
        return true;
    }

    /**
     * Read session data
     *
     * @param string $id
     */
    public function read($id)
    {
        try {
            $sql = "SELECT {$this->tableCols['data']} FROM {$this->tableName}";
            $sql .= " WHERE {$this->tableCols['session_id']} = :id";
            $stmt = $this->dbh->prepare($sql);
            $stmt->execute([':id' => $id]);
            $res = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            throw new \RuntimeException('PDOException was thrown when trying to read the session data.');
        }

        if (count($res) > 0) {
            return isset($res[0][$this->tableCols['data']]) ? base64_decode($res[0][$this->tableCols['data']]) : '';
        } else {
            return '';
        }
    }

    /**
     * Write Session
     *
     * @param string $id
     * @param mixed $data
     */
    public function write($id, $data)
    {
        $data = base64_encode($data);

        try {
            $sql = "SELECT {$this->tableCols['data']} FROM {$this->tableName}";
            $sql .= " WHERE {$this->tableCols['session_id']} = :id";
            $stmt = $this->dbh->prepare($sql);
            $stmt->execute([':id' => $id]);
            $res = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            throw new \RuntimeException('PDOException was thrown when trying to write the session data.');
        }

        try {
            if (count($res) > 0) {
                $sql = "UPDATE {$this->tableName}";
                $sql .= " SET {$this->tableCols['last_impression']} = :last_impression,";
                $sql .= " {$this->tableCols['data']} = :data";
                $sql .= " WHERE {$this->tableCols['session_id']} = :id";
                $stmt = $this->dbh->prepare($sql);
                $stmt->bindParam(':last_impression', time());
                $stmt->bindParam(':data', $data);
                $stmt->bindParam(':id', $id);
            } else {
                $sql = "INSERT INTO {$this->tableName}({$this->tableCols['session_id']}," .
                " {$this->tableCols['created']}, {$this->tableCols['last_impression']}," .
                " {$this->tableCols['data']}) VALUES (:id, :created, :last_impression, :data)";
                $stmt = $this->dbh->prepare($sql);
                $t = time();
                $stmt->bindParam(':id', $id);
                $stmt->bindParam(':created', $t);
                $stmt->bindParam(':last_impression', $t);
                $stmt->bindParam(':data', $data);
            }
            $res = $stmt->execute();
        } catch (\PDOException $e) {
            throw new \RuntimeException('PDOException was thrown when trying to write the session data.');
        }
        return true;
    }

    /**
     * Destroy Session
     *
     * @param string $id
     */
    public function destroy($id)
    {
        try {
            $sql = "DELETE FROM {$this->tableName} WHERE {$this->tableCols['session_id']} = :id";
            $stmt = $this->dbh->prepare($sql);
            $stmt->execute([':id' => $id]);
        } catch (\PDOException $e) {
            throw new \RuntimeException('PDOException was thrown when trying to destroy the session.');
        }
        return true;
    }

    /**
     * Garbage Collection
     *
     * @param int $maxlifetime
     */
    public function gc($maxlifetime)
    {
        $time = time() - $maxlifetime;
        try {
            $sql = "DELETE FROM {$this->tableName} WHERE {$this->tableCols['last_impression']} < :time";
            $stmt = $this->dbh->prepare($sql);
            $stmt->execute([':time' => $time]);
        } catch (\PDOException $e) {
            throw new \RuntimeException('PDOException was thrown when trying to delete expired sessions.');
        }
        return true;
    }
}
