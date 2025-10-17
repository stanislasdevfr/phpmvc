<?php

namespace App\Core;

class Hydrator
{
    /**
     * Hydrate an entity from an array
     */
    public static function hydrate(object $entity, array $data): object
    {
        foreach ($data as $key => $value) {
            $method = 'set' . ucfirst($key);
            
            if (method_exists($entity, $method)) {
                $entity->$method($value);
            }
        }
        
        return $entity;
    }

    /**
     * Hydrate multiple entities from an array of arrays
     */
    public static function hydrateMultiple(string $entityClass, array $dataArray): array
    {
        $entities = [];
        
        foreach ($dataArray as $data) {
            $entity = new $entityClass();
            $entities[] = self::hydrate($entity, $data);
        }
        
        return $entities;
    }

    /**
     * Extract entity data to an array
     */
    public static function extract(object $entity): array
    {
        $data = [];
        $reflection = new \ReflectionClass($entity);
        
        foreach ($reflection->getProperties() as $property) {
            $propertyName = $property->getName();
            $getter = 'get' . ucfirst($propertyName);
            
            if (method_exists($entity, $getter)) {
                $data[$propertyName] = $entity->$getter();
            }
        }
        
        return $data;
    }
}