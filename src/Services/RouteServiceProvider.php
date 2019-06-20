<?php namespace Ryuske\LaravelExtender\Services;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Routing\Router;

abstract class RouteServiceProvider extends \Illuminate\Foundation\Support\Providers\RouteServiceProvider {
  /**
   * BaseBusinessServiceProvider constructor.
   *
   * @param Application $app
   */
  public function __construct(Application $app) {
    parent::__construct($app);

    $this->route = app(Router::class);
  }

  /**
   * @var
   */
  protected $entityName;

  /**
   * @var
   */
  protected $dataProvider;

  /**
   * @var
   */
  protected $providerDirectory;

  /**
   * @var Router
   */
  protected $route;

  /**
   * Define your route model bindings, pattern filters, etc.
   *
   * @return void
   */
  public function boot() {
    parent::boot();

    if ($this->dataProvider) {
      app()->register($this->dataProvider);
    }
    $this->registerRouteBindings();
  }

  /**
   * Register the service provider.
   *
   * @return void
   */
  public function register() {
    parent::register();

    $this->entityName && view()->addNamespace($this->entityName, $this->providerDirectory . '/../Views/');
    $this->registerBindings();
  }

  /**
   * @return void
   */
  public function map() {
    if (!empty($this->namespace)) {
      $this->route->group(
        ['namespace' => $this->namespace], function ($router) {
        require $this->providerDirectory . '/../routes.php';
      }
      );
    }
  }

  /**
   * @return void
   */
  protected function registerBindings() {

  }

  /**
   * @return void
   */
  protected function registerRouteBindings() {

  }

}
