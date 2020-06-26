<?php

namespace Rubix\ML\Persisters\Serializers;

use Rubix\ML\Persistable;
use RuntimeException;
use Stringable;

use function get_class;
use function is_object;

/**
 * Native
 *
 * The native PHP plain text serialization format.
 *
 * @category    Machine Learning
 * @package     Rubix/ML
 * @author      Andrew DalPino
 */
class Native implements Serializer, Stringable
{
    /**
     * Serialize a persistable object and return the data.
     *
     * @param \Rubix\ML\Persistable $persistable
     * @return string
     */
    public function serialize(Persistable $persistable) : string
    {
        return serialize($persistable);
    }

    /**
     * Unserialize a persistable object and return it.
     *
     * @param string $data
     * @throws RuntimeException
     * @return \Rubix\ML\Persistable
     */
    public function unserialize(string $data) : Persistable
    {
        $unserialized = unserialize($data);

        if (!is_object($unserialized)) {
            throw new RuntimeException('Unserialized data is not an object.');
        }

        if (!($unserialized instanceof Persistable)) {
            throw new RuntimeException('Unserialized object is not a ' . Persistable::class . '. Got ' . get_class($unserialized));
        }

        return $unserialized;
    }

    /**
     * Return the string representation of the object.
     *
     * @return string
     */
    public function __toString() : string
    {
        return 'Native';
    }
}
