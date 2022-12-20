<?php

namespace percipiolondon\glossary\models;

use craft\base\Model;
use craft\behaviors\EnvAttributeParserBehavior;

/**
 * percipiolondon\glossary\models\Settings
 *
 * @property array|null $sections
 */
class Settings extends Model
{
    /**
     * @var array|null
     */
    public array|null $sections = null;
}
