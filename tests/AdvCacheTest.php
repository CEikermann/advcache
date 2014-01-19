<?php
/**
 * This file is part of the ceikermann/advcache package.
 *
 * (c) 2014 Christian Eikermann
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AdvCache\Tests;

use AdvCache\AdvCache;
use Doctrine\Common\Cache\Cache as DoctrineCache;

/**
 * @author Christian Eikermann <christian@chrisdev.de>
 */
class AdvCacheTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var DoctrineCache|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockCache = null;

    /**
     * Setup test
     */
    protected function setUp()
    {
        parent::setUp();

        $this->mockCache = $this->getMock('\Doctrine\Common\Cache\Cache');
    }

    /**
     * @return AdvCache
     */
    protected function getTestObject()
    {
        return new AdvCache($this->mockCache);
    }

    /**
     * @param array $methods
     *
     * @return AdvCache|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getTestMockObject($methods = array())
    {
        return $this->getMockBuilder('AdvCache\AdvCache')
                    ->setConstructorArgs(array($this->mockCache))
                    ->setMethods($methods)
                    ->getMock();
    }

    /**
     * @group contains
     */
    public function testContains()
    {
        $this->mockCache->expects($this->once())
                        ->method('contains')
                        ->with('testcacheid')
                        ->will($this->returnValue(true));

        $result = $this->getTestObject()->contains('testcacheid');
        $this->assertTrue($result, 'Invalid result');
    }

    /**
     * @group delete
     */
    public function testDelete()
    {
        $this->mockCache->expects($this->once())
                        ->method('delete')
                        ->with('testcacheid')
                        ->will($this->returnValue(true));

        $result = $this->getTestObject()->delete('testcacheid');
        $this->assertTrue($result, 'Invalid result');
    }

    /**
     * @group getStats
     */
    public function testGetStats()
    {
        $this->mockCache->expects($this->once())
                        ->method('getStats')
                        ->will($this->returnValue(array()));

        $result = $this->getTestObject()->getStats();
        $this->assertEquals(array(), $result, 'Invalid result');
    }

    /**
     * @group fetch
     */
    public function testFetchExists()
    {
        $data = new \stdClass();

        $this->mockCache->expects($this->once())
                        ->method('fetch')
                        ->with('testcacheid')
                        ->will($this->returnValue($data));

        $result = $this->getTestObject()->fetch('testcacheid');
        $this->assertSame($data, $result, 'Invalid result');
    }

    /**
     * @group fetch
     */
    public function testFetchNotExistsWithoutDefault()
    {
        $this->mockCache->expects($this->once())
                        ->method('fetch')
                        ->with('testcacheid')
                        ->will($this->returnValue(false));

        $result = $this->getTestObject()->fetch('testcacheid');
        $this->assertFalse($result, 'Invalid result');
    }

    /**
     * @group fetch
     */
    public function testFetchNotExistsWithDefault()
    {
        $this->mockCache->expects($this->once())
            ->method('fetch')
            ->with('testcacheid')
            ->will($this->returnValue(false));

        $result = $this->getTestObject()->fetch('testcacheid', array());
        $this->assertEquals(array(), $result, 'Invalid result');
    }

    /**
     * @group fetchOrCall
     */
    public function testFetchOrCallCacheHit()
    {
        $data = new \stdClass();

        $this->mockCache->expects($this->once())
                        ->method('fetch')
                        ->with('testcacheid')
                        ->will($this->returnValue($data));

        $that = $this;

        $result = $this->getTestObject()->fetchOrCall('testcacheid', function () use ($that) {
            $that->fail('Callback should not not executed');
        });

        $this->assertEquals($data, $result, 'Invalid result');
    }

    public function dpInvalidCallbackReturns()
    {
        return array(
            array(false),
            array(null)
        );
    }

    /**
     * @dataProvider dpInvalidCallbackReturns
     *
     * @group fetchOrCall
     */
    public function testFetchOrCallCacheMissAndCallbackWithInvalidReturn($callbackReturn)
    {
        $this->mockCache->expects($this->once())
                        ->method('fetch')
                        ->with('testcacheid')
                        ->will($this->returnValue(false));

        $this->mockCache->expects($this->never())
                        ->method('save');

        $callbackExecuted = false;

        $result = $this->getTestObject()->fetchOrCall('testcacheid', function () use ($callbackReturn, &$callbackExecuted) {
            $callbackExecuted = true;
            return $callbackReturn;
        });

        $this->assertTrue($callbackExecuted, 'Callback not executed');
        $this->assertSame($callbackReturn, $result, 'Invalid result');
    }

    /**
     * @group fetchOrCall
     */
    public function testFetchOrCallCacheMissAndCallbackWithValidReturn()
    {
        $data = new \stdClass();

        $this->mockCache->expects($this->once())
                        ->method('fetch')
                        ->with('testcacheid')
                        ->will($this->returnValue(false));

        $this->mockCache->expects($this->once())
                        ->method('save')
                        ->with('testcacheid', $data);

        $callbackExecuted = false;

        $result = $this->getTestObject()->fetchOrCall('testcacheid', function () use ($data, &$callbackExecuted) {
            $callbackExecuted = true;
            return $data;
        });

        $this->assertTrue($callbackExecuted, 'Callback not executed');
        $this->assertSame($data, $result, 'Invalid result');
    }

    /**
     * @group save
     */
    public function testSaveSuccess()
    {
        $data = new \stdClass();

        $this->mockCache->expects($this->once())
                        ->method('save')
                        ->with('testcacheid', $data)
                        ->will($this->returnValue(true));

        $object = $this->getTestMockObject(array('assignCacheIdToTags'));
        $object->expects($this->once())
               ->method('assignCacheIdToTags')
               ->with('testcacheid', array());

        $result = $object->save('testcacheid', $data);

        $this->assertTrue($result, 'Invalid result');
    }

    /**
     * @group save
     */
    public function testSaveError()
    {
        $data = new \stdClass();

        $this->mockCache->expects($this->once())
                    ->method('save')
                    ->with('testcacheid', $data)
                    ->will($this->returnValue(false));

        $object = $this->getTestMockObject(array('assignCacheIdToTags'));
        $object->expects($this->never())
               ->method('assignCacheIdToTags');

        $result = $object->save('testcacheid', $data);

        $this->assertFalse($result, 'Invalid result');
    }

    /**
     * @group save
     */
    public function testSaveSuccessWithTag()
    {
        $data = new \stdClass();

        $this->mockCache->expects($this->once())
                        ->method('save')
                        ->with('testcacheid', $data)
                        ->will($this->returnValue(true));

        $object = $this->getTestMockObject(array('assignCacheIdToTags'));
        $object->expects($this->once())
               ->method('assignCacheIdToTags')
               ->with('testcacheid', array('tag1'));

        $result = $object->save('testcacheid', $data, 123, array('tag1'));

        $this->assertTrue($result, 'Invalid result');
    }

    /**
     * @group deleteByTags
     */
    public function testDeleteByTags()
    {
        $object = $this->getTestMockObject(array('deleteByTag'));
        $object->expects($this->at(0))
                ->method('deleteByTag')
                ->with('tag1');

        $object->expects($this->at(1))
                ->method('deleteByTag')
                ->with('tag2');

        $object->deleteByTags(array('tag1', 'tag2'));
    }

    /**
     * @group deleteByTag
     */
    public function testDeleteByTagNoCacheIds()
    {
        $this->mockCache->expects($this->once())
                        ->method('fetch')
                        ->with('__advcache_tag[tag1]')
                        ->will($this->returnValue(false));

        $this->getTestObject()->deleteByTag('tag1');
    }

    /**
     * @group deleteByTag
     */
    public function testDeleteByTag()
    {
        $this->mockCache->expects($this->once())
                        ->method('fetch')
                        ->with('__advcache_tag[tag1]')
                        ->will($this->returnValue(array('testcacheid1', 'testcacheid2')));

        $this->mockCache->expects($this->at(1))
                        ->method('delete')
                        ->with('testcacheid1');

        $this->mockCache->expects($this->at(2))
                        ->method('delete')
                        ->with('testcacheid2');

        $this->getTestObject()->deleteByTag('tag1');
    }

    /**
     * @group assignCacheIdToTags
     */
    public function testAssignCacheIdToTags()
    {
        $object = $this->getTestMockObject(array('assignCacheIdToTag'));
        $object->expects($this->at(0))
               ->method('assignCacheIdToTag')
               ->with('testcacheid', 'tag1');
        $object->expects($this->at(1))
               ->method('assignCacheIdToTag')
               ->with('testcacheid', 'tag2');

        $object->assignCacheIdToTags('testcacheid', array('tag1', 'tag2'));
    }

    /**
     * @group assignCacheIdToTag
     */
    public function testAssignCacheIdToTagEmptyTagCache()
    {
        $this->mockCache->expects($this->once())
                        ->method('fetch')
                        ->with('__advcache_tag[tag1]')
                        ->will($this->returnValue(false));

        $this->mockCache->expects($this->once())
                        ->method('save')
                        ->with('__advcache_tag[tag1]', array('testcacheid'));

        $this->getTestObject()->assignCacheIdToTag('testcacheid', 'tag1');
    }

    /**
     * @group assignCacheIdToTag
     */
    public function testAssignCacheIdToTag()
    {
        $this->mockCache->expects($this->once())
                        ->method('fetch')
                        ->with('__advcache_tag[tag1]')
                        ->will($this->returnValue(array('testcacheid2')));

        $this->mockCache->expects($this->once())
                        ->method('save')
                        ->with('__advcache_tag[tag1]', array('testcacheid2', 'testcacheid1'));

        $this->getTestObject()->assignCacheIdToTag('testcacheid1', 'tag1');
    }

    /**
     * @group assignCacheIdToTag
     */
    public function testAssignCacheIdToTagWithDuplicatedCacheId()
    {
        $this->mockCache->expects($this->once())
                        ->method('fetch')
                        ->with('__advcache_tag[tag1]')
                        ->will($this->returnValue(array('testcacheid2', 'testcacheid1')));

        $this->mockCache->expects($this->once())
                        ->method('save')
                        ->with('__advcache_tag[tag1]', array('testcacheid2', 'testcacheid1'));

        $this->getTestObject()->assignCacheIdToTag('testcacheid2', 'tag1');
    }

}