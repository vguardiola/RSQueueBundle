<?php

/**
 * RSQueueBundle for Symfony2
 *
 * Marc Morera 2013
 */

namespace Mmoreram\RSQueueBundle\Tests\Serializer;

use Mmoreram\RSQueueBundle\Serializer\IgbinarySerializer;

/**
 * Tests JsonSerializer class
 */
class IgbinarySerializerSerializerTest extends \PHPUnit_Framework_TestCase
{

    /**
     * Tests json serializer apply method
     */
    public function testApply()
    {
        if(!function_exists('igbinary_serialize')) {
            return;
        }
        $serializer = new IgbinarySerializer();
        $data = [
            'foo' => 'foodata',
            'engonga' => 'someengongadata',
        ];
        $serializedData = $serializer->apply($data);
        $this->assertEquals($serializedData, '{"foo":"foodata","engonga":"someengongadata"}');
    }


    /**
     * Test json serializer revert method
     */
    public function testRevert()
    {
        if(!function_exists('igbinary_serialize')) {
            return;
        }
        $serializer = new IgbinarySerializer();
        $data = '{"foo":"foodata","engonga":"someengongadata"}';
        $unserializedData = $serializer->revert($data);
        $this->assertEquals(
            $unserializedData,
            [
                'foo' => 'foodata',
                'engonga' => 'someengongadata',
            ]
        );
    }
}