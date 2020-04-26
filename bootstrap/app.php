<?php

require_once __DIR__ . '/../vendor/autoload.php';
date_default_timezone_set(env('APP_TIMEZONE', 'UTC'));

use Laravel\Lumen\Routing\Router;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

class ExceptionHandler extends \Laravel\Lumen\Exceptions\Handler
{
    protected $dontReport = [
        \Illuminate\Auth\Access\AuthorizationException::class,
        \Symfony\Component\HttpKernel\Exception\HttpException::class,
        \Illuminate\Database\Eloquent\ModelNotFoundException::class,
        \Illuminate\Validation\ValidationException::class,
    ];
}

class ClosureCommand extends \Illuminate\Console\Command
{
    protected $callback;

    public function __construct($signature, Closure $callback)
    {
        $this->callback = $callback;
        $this->signature = $signature;

        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $inputs = array_merge($input->getArguments(), $input->getOptions());

        $parameters = [];

        foreach ((new ReflectionFunction($this->callback))->getParameters() as $parameter) {
            if (isset($inputs[$parameter->name])) {
                $parameters[$parameter->name] = $inputs[$parameter->name];
            }
        }

        return (int) $this->laravel->call(
            $this->callback->bindTo($this, $this),
            $parameters
        );
    }

    public function describe($description)
    {
        $this->setDescription($description);

        return $this;
    }
}

class VaporServiceProviderExtended extends \Laravel\Vapor\VaporServiceProvider
{
    public function ensureRoutesAreDefined()
    {
        /** @var \Laravel\Lumen\Routing\Router */
        $router = $this->app->make(Router::class);

        $router->group(['middleware' => 'web'], function ($router) {
            $router->post(
                '/vapor/signed-storage-url',
                \Laravel\Vapor\Contracts\SignedStorageUrlController::class . '@store'
            );
        });
    }

    protected function registerCommands()
    {
        if (!$this->app->runningInConsole()) {
            return;
        }

        $this->app->make(Illuminate\Contracts\Console\Kernel::class)->getConsole()->add(
            new ClosureCommand('vapor:handle {payload}', function () {
                throw new \InvalidArgumentException(
                    'Unknown event type. Please create a vapor:handle command to handle custom events.'
                );
            })
        );

        $this->app->singleton('command.vapor.work', function ($app) {
            return new \Laravel\Vapor\Console\Commands\VaporWorkCommand($app['queue.vaporWorker']);
        });

        $this->commands(['command.vapor.work']);
    }
}

class LumenApplicationExtended extends Laravel\Lumen\Application
{
    public function getLoadedConfigurations()
    {
        return $this->loadedConfigurations;
    }

    public function routesAreCached()
    {
        return false;
    }
};

class LumenConsoleExtended extends Laravel\Lumen\Console\Kernel
{
    public function getConsole()
    {
        return $this->getArtisan();
    }
}

class LumenKernel
{
    protected $app;

    public function __construct(LumenApplicationExtended $app)
    {
        $this->app = $app;
    }

    public function bootstrap()
    {
    }

    public function handle()
    {
        return $this->app->handle($this->app->make('request'));
    }

    public function terminate($request, $response)
    {
    }
}

$app = new LumenApplicationExtended(
    realpath(__DIR__ . '/../')
);

$app->withFacades();
// $app->withEloquent();

$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    ExceptionHandler::class
);

$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    LumenConsoleExtended::class
);

$app->configure('app');
$app->configure('cache');
$app->configure('queue');
// $app->configure('database');

$app->make('config')->set('cache.stores.dynamodb', [
    'driver' => 'dynamodb',
    'key' => env('AWS_ACCESS_KEY_ID'),
    'secret' => env('AWS_SECRET_ACCESS_KEY'),
    'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    'table' => env('DYNAMODB_CACHE_TABLE', 'cache'),
    'endpoint' => env('DYNAMODB_ENDPOINT'),
]);

$app->make(Illuminate\Contracts\Console\Kernel::class)->getConsole()->add(
    new ClosureCommand('config:cache', function () use ($app) {
        $config = $app->make('config')->all();

        file_put_contents(
            '/tmp/storage/bootstrap/cache/config.php',
            '<?php return ' . var_export($config, true) . ';' . PHP_EOL
        );
    })
);

$app->make(Illuminate\Contracts\Console\Kernel::class)->getConsole()->add(
    new ClosureCommand('config:clear', function () use ($app) {
        unlink('/tmp/storage/bootstrap/cache/config.php');
    })
);

// $app->middleware([
//     App\Http\Middleware\ExampleMiddleware::class
// ]);

// $app->routeMiddleware([
//     'auth' => App\Http\Middleware\Authenticate::class,
// ]);

$app->register(VaporServiceProviderExtended::class);

$app->bind(\Illuminate\Contracts\Http\Kernel::class, function ($app) {
    return new LumenKernel($app);
});

$app->router->group(['namespace' => 'LumenVapor\Handlers'], function () use ($app) {
    $yaml = Yaml::parseFile(
        realpath(__DIR__ . '/../lumen-vapor.yml')
    );

    collect($yaml['handlers'])->each(function ($handler, $name) use ($app) {
        [$class, $action] = explode('.', $handler['handler']);

        $app->router->{$handler['method']}(
            $handler['path'],
            $class . '@' . $action
        );
    });
});

return $app;
