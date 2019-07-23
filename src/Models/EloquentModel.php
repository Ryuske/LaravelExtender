<?php namespace Ryuske\LaravelExtender\Models;

use Ryuske\LaravelExtender\Exceptions\MethodNotFound;
use Ryuske\LaravelExtender\Exceptions\TypeMismatch;
use Ryuske\LaravelExtender\Models\Contracts\ModelAsServiceContract;
use Ryuske\LaravelExtender\Models\Structs\QueryStructContract;
use Ryuske\LaravelExtender\Models\Traits\RelatedModelMapper;
use Ryuske\LaravelExtender\Services\SingletonServiceProvider;
use Exception;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;
use JsonSerializable;
use ReflectionParameter;

/**
 * @TODO This class is pretty heavy, might make sense to break it into multiple traits or something
 */
abstract class EloquentModel implements ModelAsServiceContract, Arrayable, Jsonable, JsonSerializable {
  use RelatedModelMapper;

  /**
   * @var Model
   */
  protected $eloquentModel;

  /**
   * @var Collection
   */
  protected $modelCollection;

  /**
   * @var SingletonServiceProvider
   */
  protected $serviceProvider;

  /**
   * @var SingletonServiceProvider
   */
  private $_loadedServiceProvider;

  /**
   * EloquentModel constructor.
   *
   * @param null $model
   */
  public function __construct($model=NULL) {
    if (!property_exists($this, 'fieldMapper')) {
      $this->fieldMapper        = [];
    }

    if (!property_exists($this, 'relatedModelMapper')) {
      $this->relatedModelMapper = [];
    }

    if ($model instanceof Model) {
      $this->buildFromEloquent($model);
    }
  }

  /**
   * @param $method
   * @param array $parameters
   * @return mixed
   * @throws MethodNotFound
   */
  public function __call($method, $parameters=[]) {
    if (method_exists($this, $method)) {
      return call_user_func_array([$this, $method], $parameters);
    }

    if (method_exists($this->eloquent(), $method)) {
      return call_user_func_array([$this->eloquent(), $method], $parameters);
    }

    /**
     * @TODO This might make more sense as it's own method.
     *
     * =====
     * Related entity getters & setters
     * =====
     */
    $accessorTypeLength = 3;
    $accessorType       = substr($method, 0,4);

    if ('load' === $accessorType) {
      $accessorTypeLength = 4;
    } else {
      $accessorType       = substr($method, 0, $accessorTypeLength);
    }

    $possibleEntityName = substr($method, $accessorTypeLength);
    $maybeId            = substr($possibleEntityName, -2);

    if ('Id' === $maybeId) {
      $possibleEntityName = substr($possibleEntityName, 0, -2);
    } else {
      $maybeId = '';
    }

    // Turn PasswordReset into password_reset
    $possibleEntityNameLowercase = ltrim(strtolower(preg_replace('/[A-Z]([A-Z](?![a-z]))*/', '_$0', $possibleEntityName)), '_');

    if (array_key_exists($possibleEntityNameLowercase, $this->relatedModelMapper)) {
      $relationship = $this->eloquent()->{lcfirst($possibleEntityName)}();

      if ($relationship instanceof BelongsTo) {
        if ('get' === $accessorType || 'load' === $accessorType || $maybeId) {
          $parameters = array_prepend($parameters, new $this->relatedModelMapper[$possibleEntityNameLowercase]);
        }

        return call_user_func_array([$this, "{$accessorType}OwnerEntity{$maybeId}"], $parameters);
      } elseif ($relationship instanceof HasOne) {
        if ('get' === $accessorType || 'load' === $accessorType) {
          $parameters = array_prepend($parameters, new $this->relatedModelMapper[$possibleEntityNameLowercase]);
        }

        return call_user_func_array([$this, "{$accessorType}HasEntity"], $parameters);
      }  elseif ($relationship instanceof HasMany) {
        if ('get' === $accessorType || 'load' === $accessorType) {
          $parameters = array_prepend($parameters, new $this->relatedModelMapper[$possibleEntityNameLowercase]);
        }

        return call_user_func_array([$this, "{$accessorType}HasEntities"], $parameters);
      }
    }
    /**
     * =====
     * End related entity getters & setters
     * =====
     */

    throw new MethodNotFound($method, [$this, $this->eloquent()]);
  }

  /**
   * @param $name
   * @return mixed
   */
  public function __get($name) {
    if (property_exists($this, $name)) {
      return $this->$name;
    }

    return null;
  }

  /**
   * @return Collection
   */
  public function queryAll() {
    $entities = $this->eloquent()->all();
    $collection = $this->collection();

    foreach($entities as $entity) {
      if ($entity instanceof Model) {
        $entity = (clone $this)->buildFromEloquent($entity);
      }

      $collection->put($entity->getId(), $entity);
    }

    return $collection;
  }

  /**
   * @param QueryStructContract $parameters
   * @return $this
   */
  public function querySingle(QueryStructContract $parameters) {
    $query = $this->buildQuery($parameters)->first($parameters->getReturnedData());

    if (!$query) {
      return clone $this;
    }

    return $this->buildFromEloquent($query, $parameters);
  }

  /**
   * @param QueryStructContract $parameters
   * @return Collection
   */
  public function querySearchBy(QueryStructContract $parameters) {
    $entities   = $this->buildQuery($parameters);
    $collection = $this->collection();

    if ($parameters->getPagination() > 0) {
      $entities = $entities->paginate($parameters->getPagination(), $parameters->getReturnedData());
      $collection->setPaginator($entities);
    } else {
      $entities = $entities->get($parameters->getReturnedData());
    }

    foreach($entities as $entity) {
      if ($entity instanceof Model) {
        $entity = (clone $this)->buildFromEloquent($entity, $parameters);
      }

      $collection->put($entity->getId(), $entity);
    }

    return $collection;
  }

  /**
   * @param QueryStructContract $parameters
   * @return int
   */
  public function queryCount(QueryStructContract $parameters): int {
    return (int) $this->buildQuery($parameters)->count();
  }

  /**
   * @param QueryStructContract $parameters
   * @return int
   */
  public function querySum(QueryStructContract $parameters): int {
    return (int) $this->buildQuery($parameters)->sum($parameters->getSum());
  }

  /**
   * @return $this
   */
  public function create() {
    $model = $this->eloquent();

    $model->forceFill($this->rawFieldsAsArray());

    $model->save();

    return $this->buildFromEloquent($model);
  }

  public function save() {
    $model = $this->eloquent();

    $model->forceFill($this->rawFieldsAsArray());
    $model->exists = true;

    $model->save();
  }

  public function delete() {
    $model = $this->eloquent();

    $model->setAttribute('id', $this->getId());
    $model->exists = true;

    $model->delete();
  }

  public function forceDelete() {
    $model = $this->eloquent();

    $model->setAttribute('id', $this->getId());
    $model->exists = true;

    $model->forceDelete();
  }

  /**
   * @param Model $modal
   * @param QueryStructContract|null $queryParameters
   * @return $this
   */
  public function buildFromEloquent(Model $model, ?QueryStructContract $queryParameters=NULL) {
    /**
     * Use Eloquent model properties on the default entity setter methods defined in the fieldMapper.
     * This populates the data on an entity, from the data in an Eloquent model
     */
    foreach ($model->toArray() as $field=>$value) {
      $setter = 'set' . str_replace('_', '', ucwords(ucwords($field, "-_ \t\r\n\f\v")));

      try {
        /**
         * Call the setter method (->setSomeField) and pass the Eloquent property (->some_field) as the parameter
         */
        if (isset($model->{$field})) {
          $value = $this->castToParameterType([$this, $setter], $model->{$field});

          if (NULL !== $value && !$value instanceof Model) {
            $this->{$setter}($value);
          }
        }
      } catch (MethodNotFound $exception) {}
    }

    /**
     * Define an explicit relationship between an eloquent field and a model field.
     * Used if the default won't work, or the field names don't match
     */
    foreach ($this->fieldMapper as $field=>$setter) {
      $setter = "set$setter";

      if (method_exists($this, $setter)) {
        /**
         * Call the setter method (->setSomeField) and pass the Eloquent property (->some_field) as the parameter
         */
        if (isset($model->{$field}) && !empty($model->{$field})) {
          $this->{$setter}($this->castToParameterType([$this, $setter], $model->{$field}));
        }
      }
    }

    /**
     * Used to set autoloaded/child entities on the given/parent entity, based on what exists in the Eloquent model
     */
    if ($queryParameters) {
      // Set the array value as the key in $queryParameters->getWith()
      $requestedModels = [];

      foreach ($queryParameters->getWith() as $parameter) {
        if (is_array($parameter)) {
          $requestedModels[$parameter['model']] = $parameter['model'];
        } else {
          $requestedModels[$parameter] = $parameter;
        }
      }

      $relatedModels   = array_intersect_key($this->relatedModelMapper, $requestedModels);

      foreach ($relatedModels as $modelName => $modelFQN) {
        /**
         * Create a new instance of the entity being autoloaded
         */
        $modelInstance      = new $modelFQN();
        $formattedModelName = ucwords($modelName);
        $setterMethod       = "set$formattedModelName";

        if ($model->{$modelName} instanceof \Illuminate\Database\Eloquent\Collection) {
          $getterMethod = "get$formattedModelName";

          if (is_callable([$this, $getterMethod])) {
            /**
             * Get a collection of the related models
             */
            $modelInstance = $this->{$getterMethod}();
          }

          foreach ($model->{$modelName} as $relatedModel) {
            $modelInstance->push(
              new $modelFQN($relatedModel)
            );
          }
        } else {
          /**
           * Pass the instance of the autoloaded/child entity that is currently set on the given/parent entity.
           * This makes sure that if data was filled out along the way, it's kept once we finish building the new
           * model from the Eloquent data
           */
          $modelInstance = new $modelFQN($model->{$modelName});
        }
        /**
         * Set the ID of the autoloaded/child entity, from the corresponding Eloquent property (->entity_id)
         */

        if (is_callable([$this, $setterMethod])) {
          /**
           * Set the instance of the autoloaded/child entity that was created above on the given/parent entity
           */
          $this->{$setterMethod}($modelInstance);
        }
      }
    }

    return $this;
  }

  /**
   * @return array
   */
  public function toArray(): array {
    $fields = $this->arrayableFields();

    foreach ($fields as $key=>&$field) {
      if (is_object($field)) {
        if (method_exists($field, 'toArray')) {
          $field = $field->toArray();
        } else {
          unset($fields[$key]);
        }
      }
    }

    return $fields;
  }

  /**
   * @param int $options
   * @return string
   */
  public function toJson($options = 0): string {
    return json_encode($this->jsonSerialize(), $options);
  }

  public function jsonSerialize() {
    return $this->toArray();
  }

  /**
   * @return bool
   */
  public function isEmpty(): bool {
    return empty($this->_fields);
  }

  /**
   * @param QueryStructContract $parameters
   * @return Model
   */
  protected function buildQuery(QueryStructContract $parameters) {
    $queryableModel        = $parameters->getEloquent();
    $whereParameters       = $parameters->getWhere();
    $joinParameters        = $parameters->getJoins();
    $withParameters        = $parameters->getWith();
    $withTrashedParameters = [];
    $orderByParameters     = $parameters->getOrderBy();
    $groupByParameters     = $parameters->getGroupBy();
    $isDistinct            = $parameters->getDistinct();

    foreach ($whereParameters as $field=>$value) {
      if (is_array($value)) {
        switch (strtolower($value['operator'])) {
          case 'in':
            $queryableModel = $queryableModel->whereIn($field, $value['value']);
            break;
          case 'date':
            $queryableModel = $queryableModel->whereDate($field, $value['date_operator'], $value['value']);
            break;
          case 'between':
            $queryableModel = $queryableModel->whereBetween($field, $value['value']);
            break;
          case 'raw':
            $queryableModel = $queryableModel->whereRaw($value['statement'], $value['value']);
            break;
          default:
            $queryableModel = $queryableModel->where($field, $value['operator'], $value['value']);
        }
      } else {
        $queryableModel = $queryableModel->where($field, $value);
      }
    }

    if (!empty($withParameters)) {
      $eloquentModel = $queryableModel;

      if ($queryableModel instanceof Builder) {
        $eloquentModel = $queryableModel->getModel();
      }

      foreach($withParameters as $key=>$parameter) {
        if (is_array($parameter)) {
          if (isset($parameter['include_deleted']) && true === $parameter['include_deleted']) {
            if (method_exists($eloquentModel, $parameter['model'])) {
              $withTrashedParameters[$parameter['model']] = function($query) {$query->withTrashed();};
            }

            unset($withParameters[$key]);
          } else {
            if (method_exists($eloquentModel, $parameter['model'])) {
              $withParameters[$key] = $parameter['model'];
            } else {
              unset($withParameters[$key]);
            }
          }
        } else {
          if (!method_exists($eloquentModel, $parameter)) {
            unset($withParameters[$key]);
          }
        }
      }

      unset($eloquentModel);

      $queryableModel = $queryableModel->with($withTrashedParameters);
      $queryableModel = $queryableModel->with($withParameters);
    }

    if (!empty($joinParameters)) {
      foreach($joinParameters as $key=>$parameter) {
        $joinMethod = $parameter['type'] ?? 'inner';
        
        $queryableModel->join($key, $parameter['table_2_id'], $parameter['operator'], $parameter['table_1_id'], $joinMethod);
      }
    }

    if (!empty($orderByParameters)) {
      $queryableModel = $queryableModel->orderBy($orderByParameters['field'], $orderByParameters['order']);
    }

    if (!empty($groupByParameters)) {
      $queryableModel = $queryableModel->groupBy($groupByParameters);
    }

    if ($isDistinct) {
      $queryableModel = $queryableModel->distinct();
    }

    return $queryableModel;
  }

  /**
   * @return Model
   */
  public function eloquent(): Model {
    return app($this->eloquentModel);
  }

  /**
   * @return Model
   * @DEPRECATED
   */
  protected function _eloquent(): Model {
    return $this->eloquent();
  }

  /**
   * @param $method
   * @param $value
   * @param int $argument
   * @return NULL|\ReflectionType
   */
  protected function castToParameterType($method, $value, int $argument=0) {
    try {
      $parameterType = (string) (new ReflectionParameter($method, $argument))->getType();
    } catch (\ReflectionException $exception) {
      $parameterType = '';
    }

    if ($value instanceof $parameterType) {
      return $value;
    }

    switch ($parameterType) {
      case 'boolean':
      case 'bool':
      case 'integer':
      case 'int':
      case 'float':
      case 'double':
      case 'string':
      case 'array':
      case 'object':
      case 'null':
        settype($value, $parameterType);
        break;
      case '':
        break;
      default:
        $value = NULL;
    }

    return $value;
  }
  
  /**
   * @return SingletonServiceProvider
   * @throws Exception
   * @throws TypeMismatch
   */
  public function serviceProvider() {
    if (NULL !== $this->_loadedServiceProvider) {
      return $this->_loadedServiceProvider;
    }

    if (empty($this->serviceProvider)) {
      throw new Exception(get_class($this) . '::$serviceProvider must be set to use ->serviceProvider()');
    }

    $this->_loadedServiceProvider = app($this->serviceProvider);

    if (!$this->_loadedServiceProvider instanceof SingletonServiceProvider) {
      throw new TypeMismatch(get_class($this) . '::$serviceProvider must be instance of ' . SingletonServiceProvider::class);
    }

    return $this->_loadedServiceProvider;
  }

  /**
   * @param array $items
   * @return \App\Internals\Models\Collection
   */
  public function collection($items = []): Collection {
    return new $this->modelCollection($items);
  }

  /**
   * The fields that should be output when using self::toArray()
   *
   * @return array
   */
  protected function arrayableFields(): array {
    return $this->_fields;
  }

  /**
   * @return array
   */
  abstract protected function rawFieldsAsArray(): array;

  /**
   * @param int|null $id
   * @return mixed
   */
  abstract public function setId(?int $id);

  /**
   * @return int
   */
  abstract public function getId(): int;
}
