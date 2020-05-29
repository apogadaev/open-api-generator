<?php

declare(strict_types=1);

namespace Speicher210\OpenApiGenerator\Describer;

use cebe\openapi\spec\Reference;
use cebe\openapi\spec\Schema;
use RuntimeException;
use Speicher210\OpenApiGenerator\Describer\ObjectDescriber\Describer;
use Speicher210\OpenApiGenerator\Model\Definition;
use Speicher210\OpenApiGenerator\Model\ModelRegistry;
use function implode;
use function sprintf;

final class ObjectDescriber
{
    private ModelRegistry $modelRegistry;

    /** @var Describer[] */
    private array $describers;

    public function __construct(ModelRegistry $modelRegistry, Describer ...$describers)
    {
        $this->modelRegistry = $modelRegistry;
        $this->describers    = $describers;
    }

    public function describe(Definition $definition) : Schema
    {
        if (! $this->modelRegistry->schemaExistsForDefinition($definition)) {
            $this->modelRegistry->addSchema(
                $definition,
                $this->createSchema($definition),
            );
        }

        return $this->modelRegistry->getSchema($definition);
    }

    public function describeAsReference(Definition $definition, string $referencePath) : Reference
    {
        $this->describe($definition);

        return $this->modelRegistry->createReference($definition, $referencePath);
    }

    private function createSchema(Definition $definition) : Schema
    {
        foreach ($this->describers as $describer) {
            if ($describer->supports($definition)) {
                $schema = new Schema([]);
                $describer->describeInSchema($schema, $definition, $this);

                return $schema;
            }
        }

        throw new RuntimeException(
            sprintf(
                'Definition with class name "%s" and serialization groups "%s" can not be described.',
                $definition->className(),
                implode(', ', $definition->serializationGroups())
            )
        );
    }
}
