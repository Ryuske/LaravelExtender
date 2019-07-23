<?php namespace Ryuske\LaravelExtender\Models\Traits;

use Ryuske\LaravelExtender\Exceptions\MethodNotFound;
use Ryuske\LaravelExtender\Models\Collection;
use Ryuske\LaravelExtender\Models\EloquentModel;
use Exception;
use ReflectionClass;

trait RelatedModelMapper {
  private $loadedEntities = [];

  /**
   * @param EloquentModel $entity
   * @return $this
   */
  public function setHasEntity(EloquentModel $entity) {
    $className  = get_class($entity);
    $entityName = array_search($className, $this->relatedModelMapper);

    if (false === $entityName) {
      throw new Exception("There is no related model setup for $className in " . get_class($this));
    }

    if (!$entity->isEmpty()) {
      $this->loadedEntities[$className] = true;
    }

    $this->_fields[$entityName] = $entity;

    return $this;
  }

  /**
   * This will return an instance of whatever is passed into $entity
   *
   * @param EloquentModel $entity
   * @return EloquentModel
   * @throws MethodNotFound
   */
  public function getHasEntity(EloquentModel $entity) {
    $entityName      = array_search(get_class($entity), $this->relatedModelMapper);
    $parentClassName = (new ReflectionClass($this))->getShortName();
    $setterName      = "set$parentClassName";

    /**
     * Checks to see if the entity exists in $this->_fields
     * For example: $this->_fields['account'], or $this->_fields['my_model']
     */
    if (array_key_exists($entityName, $this->_fields) && $this->_fields[$entityName] instanceof $entity) {
      /**
       * Checks if the child entity has a setter for the parent entity.
       * For example: Account HasMany Comments, so this checks to see if Comments has a setAccount method
       */
      if (!is_callable([$this, $setterName])) {
        throw new MethodNotFound($setterName, [$entity]);
      }

      $this->_fields[$entityName]->$setterName($this);

      return $this->_fields[$entityName];
    }

    return new $entity;
  }

  /**
   * This is used to load a collection of entities, either eagerly or on-demand
   *
   * @param EloquentModel $entity
   * @return $this
   * @throws MethodNotFound
   */
  public function loadHasEntity(EloquentModel $entity) {
    $className             = get_class($entity);
    $entityClassName       = (new ReflectionClass($entity))->getShortName();
    $entityGetter          = "get$entityClassName";
    $entitySetter          = "set$entityClassName";
    $whereThisEntitySetter = "setWhere" . (new ReflectionClass($entity))->getShortName();

    if ($this->$entityGetter()->isEmpty() && !$this->loadedEntities[$className]) {
      if (!method_exists($this->serviceProvider()->searchByStruct(), $whereThisEntitySetter)) {
        throw new MethodNotFound($whereThisEntitySetter, [$this->serviceProvider()->searchByStruct()]);
      }

      $this->loadedEntities[$className] = true;

      /**
       * SELECT all of the $entity records that have an association to $this
       */
      $loadedEntity = $this->serviceProvider()->searchBy(
        $this->serviceProvider()->searchByStruct()->$whereThisEntitySetter($this)
      );

      return $this->$entitySetter($loadedEntity);
    }

    return $this;
  }

  /**
   * This will return a collection of $entities
   *
   * @param Collection $entities
   * @return $this
   */
  public function setHasEntities(Collection $entities) {
    $className  = get_class($entities->get(0));
    $entityName = array_search($className, $this->relatedModelMapper);

    if (false === $entityName) {
      throw new Exception("There is no related model setup for $className in " . get_class($this));
    }

    if (!$entities->isEmpty()) {
      $this->loadedEntities[$className] = true;
    }

    $this->_fields[$entityName] = $entities;

    return $this;
  }

  /**
   * This will return a Collection of whatever is passed into $entity
   *
   * @param EloquentModel $entity
   * @return Collection
   * @throws MethodNotFound
   */
  public function getHasEntities(EloquentModel $entity) {
    $entityName       = array_search(get_class($entity), $this->relatedModelMapper);
    $parentClassName  = (new ReflectionClass($this))->getShortName();
    $setterName       = "set$parentClassName";
    $entityCollection = $entity->collection();

    /**
     * Checks to see if the entity exists in $this->_fields
     * For example: $this->_fields['account'], or $this->_fields['my_model']
     */
    if (array_key_exists($entityName, $this->_fields) && $this->_fields[$entityName] instanceof $entityCollection) {
      /**
       * Checks if the child entity has a setter for the parent entity.
       * For example: Account HasMany Comments, so this checks to see if Comments has a setAccount method
       */
      if (!is_callable([$this, $setterName])) {
        throw new MethodNotFound($setterName, [$entity]);
      }

      /**
       * This would need to be a foreach and do it for all of the entities in the collection..
       * Not sure that's necessary, and would be a lot of overhead to do every time.
       * So, I'll think about this some more, or deal with it when the need comes up.
       */
      //$this->_fields[$entityName]->$setterName($this);

      return $this->_fields[$entityName];
    }

    return $entity->collection();
  }

  /**
   * This is used to load a collection of entities, either eagerly or on-demand
   *
   * @param EloquentModel $entity
   * @return $this
   * @throws MethodNotFound
   */
  public function loadHasEntities(EloquentModel $entity) {
    return $this->loadHasEntity($entity);
  }

  /**
   * @param EloquentModel $entity
   * @param int $id
   * @return $this
   */
  public function setOwnerEntityId(EloquentModel $entity, int $id) {
    $entityClassName = (new ReflectionClass($entity))->getShortName();
    $entityGetter    = "get$entityClassName";
    $entitySetter    = "set$entityClassName";

    $this->$entitySetter($this->$entityGetter()->setId($id));

    return $this;
  }

  /**
   * @param EloquentModel $entity
   * @return $this
   */
  public function setOwnerEntity(EloquentModel $entity) {
    $className  = get_class($entity);
    $entityName = array_search($className, $this->relatedModelMapper);

    if (false === $entityName) {
      throw new Exception("There is no related model setup for $className in " . get_class($this));
    }

    $this->_fields[$entityName] = $entity;

    return $this;
  }

  /**
   * @return EloquentModel
   */
  public function getOwnerEntity(EloquentModel $entity) {
    $className  = get_class($entity);
    $entityName = array_search($className, $this->relatedModelMapper);

    if (false === $entityName) {
      throw new Exception("There is no related model setup for $className in " . get_class($this));
    }

    if (array_key_exists($entityName, $this->_fields) && $this->_fields[$entityName] instanceof $entity) {
      return $this->_fields[$entityName];
    }

    return new $entity;
  }

  /**
   * This is used to load an entity, either eagerly or on-demand
   *
   * @param EloquentModel $entity
   * @return $this
   * @throws MethodNotFound
   */
  public function loadOwnerEntity(EloquentModel $entity) {
    $className       = get_class($entity);
    $entityClassName = (new ReflectionClass($entity))->getShortName();
    $entityGetter    = "get$entityClassName";
    $entitySetter    = "set$entityClassName";

    $this->loadedEntities[$className] = true;

    /**
     * SELECT $entity record that has an association to $this
     */
    $loadedEntity = $entity->serviceProvider()->single(
      $entity->serviceProvider()->singleStruct()->setWhereId($this->$entityGetter()->getId())
    );

    return $this->$entitySetter($loadedEntity);
  }
}