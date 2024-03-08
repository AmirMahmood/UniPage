<?php

use Psr\Container\ContainerInterface;
use Slim\App;
use Slim\Factory\AppFactory;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;
use Slim\Csrf\Guard;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Setup;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Doctrine\Common\Cache\Psr6\DoctrineProvider;

return [
    'settings' => function () {
        return require __DIR__ . '/settings.php';
    },

    Twig::class => function (ContainerInterface $container) {
        $settings = $container->get('settings');

        return Twig::create(
            $settings['twig']['template_dir'],
            [
                'cache' => $settings['twig']['cache']
            ]
        );
    },

    TwigMiddleware::class => function (ContainerInterface $container) {
        return TwigMiddleware::createFromContainer(
            $container->get(App::class),
            Twig::class
        );
    },

    EntityManager::class => function (ContainerInterface $container) {
        $settings = $container->get('settings');

        // Use the ArrayAdapter or the FilesystemAdapter depending on the value of the 'dev_mode' setting
        // You can substitute the FilesystemAdapter for any other cache you prefer from the symfony/cache library
        $cache = $settings['doctrine']['dev_mode'] ?
            DoctrineProvider::wrap(new ArrayAdapter()) :
            DoctrineProvider::wrap(new FilesystemAdapter(directory: $settings['doctrine']['cache_dir']));

        $config = Setup::createAttributeMetadataConfiguration(
            $settings['doctrine']['metadata_dirs'],
            $settings['doctrine']['dev_mode'],
            null,
            $cache
        );
        return EntityManager::create($settings['doctrine']['connection'], $config);
    },

    Guard::class => function (ContainerInterface $container) {
        $app = AppFactory::createFromContainer($container);
        $responseFactory = $app->getResponseFactory();
        return new Guard($responseFactory, persistentTokenMode: true);
    },

    App::class => function (ContainerInterface $container) {
        $app = AppFactory::createFromContainer($container);

        // Register routes
        (require __DIR__ . '/routes.php')($app);

        // Register middleware
        (require __DIR__ . '/middleware.php')($app);

        return $app;
    },
];
