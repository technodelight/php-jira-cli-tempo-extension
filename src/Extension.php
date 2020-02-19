<?php

namespace
{
    spl_autoload_register(function ($className) {
        $classPaths = [
            __DIR__ . '/%s.php' => ['Technodelight\\JiraTempoExtension'],
            getenv('HOME') . '/.composer/vendor/*/*/src/%s.php' => [
                'Technodelight\\Tempo2',
                'ICanBoogie\\Storage',
            ],
        ];

        $found = false;
        foreach ($classPaths as $path => $namespaces) {
            foreach ($namespaces as $namespace) {
                if (strpos($className, $namespace) !== false) {
                    $filepath = str_replace(
                        '\\',
                        DIRECTORY_SEPARATOR,
                        str_replace($namespace, '', $className)
                    );
                    $found = [sprintf($path, $filepath), $namespace];
                }
            }
        }
        if (false === $found) {
            return;
        }

        list ($globPath, $namespace) = $found;
        foreach (glob($globPath) as $file) {
            if (is_file($file) && strpos(file_get_contents($file), 'namespace ' . $namespace) !== false) {
                require_once $file;
                break;
            }
        }
    });
}

namespace Technodelight\JiraTempoExtension
{
    use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
    use Symfony\Component\Config\Definition\Builder\TreeBuilder;
    use Symfony\Component\Config\FileLocator;
    use Symfony\Component\DependencyInjection\ContainerBuilder;
    use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
    use Technodelight\Jira\Extension\ExtensionInterface;

    class Extension implements ExtensionInterface
    {
        public function load(array $configs, ContainerBuilder $container)
        {
            $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/Resources'));
            $loader->load('services.xml');

            $def = $container->getDefinition('technodelight.jira.config.integrations.tempo');
            $def->setArguments(
                [isset($configs['tempo']) ? $configs['tempo'] : ['instances' => []]]
            );
        }

        public function configure(): ArrayNodeDefinition
        {
            $builder = new TreeBuilder;
            $node = $builder->root('tempo');

            $node
                ->info('Tempo timesheets (https://tempo.io/doc/timesheets/api/rest/latest)')
                ->addDefaultsIfNotSet()
                ->children()
                    ->arrayNode('instances')
                        ->prototype('array')
                            ->children()
                                ->scalarNode('instance')->defaultNull()->end()
                                ->scalarNode('apiToken')
                                    ->defaultNull()
                                    ->validate()
                                    ->ifEmpty()->thenInvalid('apiToken must be provided for Tempo')->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end();

            return $node;
        }
    }
}