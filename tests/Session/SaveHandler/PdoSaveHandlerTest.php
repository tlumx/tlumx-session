<?php
/**
 * Tlumx (https://tlumx.com/)
 *
 * @author    Yaroslav Kharitonchuk <yarik.proger@gmail.com>
 * @link      https://github.com/tlumx/tlumx-servicecontainer
 * @copyright Copyright (c) 2016-2018 Yaroslav Kharitonchuk
 * @license   https://github.com/tlumx/tlumx-servicecontainer/blob/master/LICENSE  (MIT License)
 */
namespace Tlumx\Tests\Session;

use Tlumx\Session\SaveHandler\PdoSaveHandler;

class PdoSaveHandlerTest extends \PHPUnit\Framework\TestCase
{
    private $dbh;

    protected function setUp()
    {
        $this->dbh = new \PDO('sqlite::memory:');
        $this->dbh->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $sql = 'CREATE TABLE sessions (session_id VARCHAR(128) PRIMARY KEY, created INTEGER,';
        $sql .= ' last_impression INTEGER, data TEXT)';
        $this->dbh->exec($sql);
    }

    public function tearDown()
    {
        unset($this->dbh);
    }

    public function testInvalidTableWrite()
    {
        $handler = new PdoSaveHandler($this->dbh, 'invalid_session_table');
        $this->expectException(\RuntimeException::class);
        $handler->write('some_id', 'some_data');
    }

    public function testInvalidTableRead()
    {
        $handler = new PdoSaveHandler($this->dbh, 'invalid_session_table');
        $this->expectException(\RuntimeException::class);
        $handler->read('some_id');
    }

    public function testWriteRead()
    {
        $handler = new PdoSaveHandler($this->dbh);
        $handler->write('some_id', 'some_data');
        $this->assertEquals('some_data', $handler->read('some_id'));
    }

    public function testMultipleInstances()
    {
        $handler1 = new PdoSaveHandler($this->dbh);
        $handler1->write('some_id', 'some_data');

        $handler2 = new PdoSaveHandler($this->dbh);
        $this->assertEquals('some_data', $handler2->read('some_id'));
    }

    public function testDestroy()
    {
        $handler = new PdoSaveHandler($this->dbh);
        $handler->write('some_id', 'some_data');

        $this->assertCount(1, $this->dbh->query('SELECT * FROM sessions')->fetchAll());

        $handler->destroy('some_id');

        $this->assertCount(0, $this->dbh->query('SELECT * FROM sessions')->fetchAll());
    }

    public function testGC()
    {
        $handler = new PdoSaveHandler($this->dbh);
        $handler->write('some_id', 'some_data');
        $handler->write('some_id2', 'some_data');

        $this->assertCount(2, $this->dbh->query('SELECT * FROM sessions')->fetchAll());

        $handler->gc(-1);
        $this->assertCount(0, $this->dbh->query('SELECT * FROM sessions')->fetchAll());
    }

    public function testElseConfigure()
    {
        $dbh = new \PDO('sqlite::memory:');
        $dbh->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $sql = 'CREATE TABLE my_sessions (sess_id VARCHAR(128) PRIMARY KEY,';
        $sql .= ' sess_created INTEGER, sess_last_impression INTEGER, sess_data TEXT)';
        $dbh->exec($sql);

        $handler = new PdoSaveHandler($dbh, 'my_sessions', [
            'session_id'        => 'sess_id',
            'created'           => 'sess_created',
            'last_impression'   => 'sess_last_impression',
            'data'              => 'sess_data'
        ]);

        $handler->write('some_id', 'some_data');
        $this->assertEquals('some_data', $handler->read('some_id'));
    }
}
