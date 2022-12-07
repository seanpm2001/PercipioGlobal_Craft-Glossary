<?php

namespace percipiolondon\glossary\gql\directives;

use Craft;
use craft\gql\base\Directive;
use craft\gql\GqlEntityRegistry;
use GraphQL\Language\DirectiveLocation;
use GraphQL\Type\Definition\Directive as GqlDirective;
use GraphQL\Type\Definition\FieldArgument;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use percipiolondon\glossary\Glossary as Plugin;

class Glossary extends Directive
{
    public static function create(): GqlDirective
    {
        if ($type = GqlEntityRegistry::getEntity(self::name())) {
            return $type;
        }

        return GqlEntityRegistry::createEntity(static::name(), new self([
            'name' => static::name(),
            'locations' => [
                DirectiveLocation::FIELD,
            ],
            'args' => [
                new FieldArgument([
                    'name' => 'handle',
                    'type' => Type::string(),
                    'defaultValue' => null,
                    'description' => 'define if a specific glossary for a certain section needs to be fetched',
                ]),
            ],
            'description' => 'Parses the text with the glossary terms and their definitions attached to it',
        ]));
    }

    public static function name(): string
    {
        return 'glossary';
    }

    public static function apply(mixed $source, mixed $value, array $arguments, ResolveInfo $resolveInfo): mixed
    {
        return Plugin::$plugin->glossaryService->renderGlossary($value);
    }
}