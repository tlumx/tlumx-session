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

use Tlumx\Session\Session;

/**
* @runTestsInSeparateProcesses
*/
class SessionTest extends \PHPUnit\Framework\TestCase
{

    private $session;

    public function setUp()
    {
        $this->session = new Session();
    }

    public function tearDown()
    {
        $this->session->destroy();
    }

    public function testGetOptions()
    {
        $options = $this->session->getOptions();
        $this->assertArrayHasKey('name', $options);

        $option = $this->session->getOptions('name');
        $this->assertEquals($option, ini_get('session.name'));
    }

    public function testInvalidGetOption()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->session->getOptions('invalid_option');
    }

    public function testSetOptions()
    {
        $this->session->setOptions(['name' => 'MySess','cookie_lifetime' => 0]);
        $this->assertEquals('MySess', ini_get('session.name'));
        $this->assertEquals(0, ini_get('session.cookie_lifetime'));
    }

    public function testInvalidSetOption()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->session->setOptions(['invalid_option' => 'val']);
    }

    /**
     * @runInSeparateProcess
     */
    public function testIsStarted()
    {
        $this->assertFalse($this->session->isStarted());
        session_start();
        $this->assertTrue($this->session->isStarted());
        session_destroy();
        $this->assertFalse($this->session->isStarted());
    }

    /**
     * @runInSeparateProcess
     */
    public function testStart()
    {
        $this->assertFalse($this->session->isStarted());
        $this->session->start();
        $this->assertTrue($this->session->isStarted());
        session_destroy();
        $this->assertFalse($this->session->isStarted());
    }

    /**
     * @runInSeparateProcess
     */
    public function testClose()
    {
        $this->session->start();
        $this->assertTrue($this->session->isStarted());
        $this->session->close();
        $this->assertFalse($this->session->isStarted());
    }

    /**
     * @runInSeparateProcess
     */
    public function testDestroy()
    {
        $this->session->start();
        $_SESSION['key'] = 'value';
        $id = session_id();
        $this->assertTrue($this->session->isStarted());
        $this->session->destroy();
        $this->assertFalse($this->session->isStarted());
        $this->assertFalse(isset($_SESSION['key']));
        $this->assertFalse($id == session_id());
    }

    /**
     * @runInSeparateProcess
     */
    public function testGetId()
    {
        $id = session_id();
        $this->assertEquals($id, $this->session->getId());
    }

    /**
     * @runInSeparateProcess
     */
    public function testInvalidSetIdBeforeStart()
    {
        $this->expectException(\InvalidArgumentException::class);
        session_start();
        $this->session->setId('my_id');
    }

    /**
     * @runInSeparateProcess
     */
    public function testSetId()
    {
        $this->session->setId('my_id');
        $this->assertEquals('my_id', $this->session->getId());
        $this->assertEquals('my_id', session_id());
    }

    /**
     * @runInSeparateProcess
     */
    public function testRegenerateID()
    {
        session_start();
        $id = session_id();
        $this->session->regenerateID();
        $newId = session_id();

        $this->assertNotSame($id, $newId);
    }

    /**
     * @runInSeparateProcess
     */
    public function testGetIniSessionName()
    {
        $ini = ini_get('session.name');
        $this->assertEquals($ini, $this->session->getName());
    }

    /**
     * @runInSeparateProcess
     */
    public function testInvalidSetNameBeforeStart()
    {
        $this->expectException(\InvalidArgumentException::class);
        session_start();
        $this->session->setName('MySess');
    }

    /**
     * @runInSeparateProcess
     */
    public function testInvalidSetNameNotAlphanumeric()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->session->setName('MySess!');
    }

    /**
     * @runInSeparateProcess
     */
    public function testSetSessionName()
    {
        $this->session->setName('MySess');
        $this->assertEquals('MySess', $this->session->getName());
        $this->assertEquals('MySess', session_name());
    }

    /**
     * @runInSeparateProcess
     */
    public function testRememberMe()
    {

        $id = session_id();
        $this->session->rememberMe(1209600);
        $this->session->start();
        $this->assertEquals(1209600, ini_get('session.cookie_lifetime'));
        $newId = session_id();
        $this->assertTrue($id != $newId);
    }

    public function testInvalidRememberMeSetLifetimeNotNumeric()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->session->rememberMe('some time');
    }

    public function testInvalidRememberMeSetLifetimeBadInteger()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->session->rememberMe(-1);
    }

    /**
     * @runInSeparateProcess
     */
    public function testForgetMe()
    {
        ini_set('session.cookie_lifetime', 1209600);
        $this->session->forgetMe();
        $this->assertEquals(0, ini_get('session.cookie_lifetime'));
    }

    /**
     * @runInSeparateProcess
     */
    public function testGet()
    {
        session_start();
        $_SESSION['key1'] = 'value1';
        $this->assertEquals($this->session->get('key1'), 'value1');
        $this->assertEquals($this->session->get('key2', false), false);
        $this->assertEquals($this->session->get('key3', 'value'), 'value');
    }

    /**
     * @runInSeparateProcess
     */
    public function testInvalidSetKeyString()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->session->set(123, 'value');
    }

    /**
     * @runInSeparateProcess
     */
    public function testInvalidSetKeyUnderscore()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->session->set("_mykey", 'value');
    }

    /**
     * @runInSeparateProcess
     */
    public function testSet()
    {
        $this->session->set('key1', 'value1');
        $this->assertArrayHasKey('key1', $_SESSION);
        $this->assertEquals('value1', $_SESSION['key1']);
    }

    /**
     * @runInSeparateProcess
     */
    public function testHas()
    {
        session_start();
        $_SESSION['key1'] = 'value1';
        $this->assertTrue($this->session->has('key1'));
    }

    /**
     * @runInSeparateProcess
     */
    public function testRemove()
    {
        session_start();
        $_SESSION['key1'] = 'value1';
        $this->session->remove('key1');
        $this->assertEquals(false, isset($_SESSION['key1']));
    }

    /**
     * @runInSeparateProcess
     */
    public function testRemoveAll()
    {
        session_start();
        $_SESSION['key1'] = 'value1';
        $_SESSION['key2'] = 'value2';
        $_SESSION['key3'] = 'value3';
        $this->session->removeAll();
        $this->assertEquals(0, count($_SESSION));
    }

    public function testSetInvalidFlashKey()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->session->flash(123, 'value');
    }

    /**
     * @runInSeparateProcess
     */
    public function testFlash()
    {
        $this->session->flash('key1', 'value1');
        $this->session->flash('key1', 'value2');
        $this->assertEquals('value2', $this->session->flash('key1'));
        $this->assertEquals(null, $this->session->flash('key1'));
    }

    /**
     * @runInSeparateProcess
     */
    public function testGetAll()
    {
        $this->session->set('key1', 'value1');
        $this->session->set('key2', 'value2');
        $this->session->set('key3', 'value3');
        $this->session->flash('flash_key1', 'flash_value1');
        $this->session->flash('flash_key2', 'flash_value2');

        $this->assertEquals(['key1' => 'value1','key2' => 'value2','key3' => 'value3'], $this->session->getAll());
    }
}
