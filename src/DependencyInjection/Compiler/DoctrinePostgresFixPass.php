<?php

declare(strict_types=1);

namespace App\DependencyInjection\Compiler;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\ORM\Mapping\ClassMetadata;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class DoctrinePostgresFixPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('doctrine.orm.default_configuration')) {
            return;
        }

        $definition = $container->getDefinition('doctrine.orm.default_configuration');

        $definition->addMethodCall('setIdentityGenerationPreferences', [
            [
                PostgreSQLPlatform::class => ClassMetadata::GENERATOR_TYPE_SEQUENCE,
            ]
        ]);
    }
}
