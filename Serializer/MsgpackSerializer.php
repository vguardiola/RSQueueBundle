<?php

/**
 * RSQueueBundle for Symfony2
 *
 * Marc Morera 2013
 */

namespace Mmoreram\RSQueueBundle\Serializer;

use Mmoreram\RSQueueBundle\Serializer\Interfaces\SerializerInterface;

/**
 * Implementation of Json Serializer
 */
class MsgpackSerializer implements SerializerInterface
{

    /**
     * Given any kind of object, apply serialization
     *
     * @param Mixed $unserializedData Data to serialize
     *
     * @return string
     */
    public function apply($unserializedData)
    {
        return msgpack_pack($unserializedData);
    }


    /**
     * Given any kind of object, apply serialization
     *
     * @param String $serializedData Data to unserialize
     *
     * @return mixed
     */
    public function revert($serializedData)
    {
        return msgpack_unpack($serializedData   );
    }
}