<?php
/**
 * Glossary plugin for Craft CMS 4.x
 *
 * Craft Plugin that synchronises with Glossary
 *
 * @link      https://percipio.london
 * @copyright Copyright (c) 2021 percipiolondon
 */

namespace percipiolondon\glossary\variables;

use Craft;

use nystudio107\pluginvite\variables\ViteVariableInterface;
use nystudio107\pluginvite\variables\ViteVariableTrait;

use percipiolondon\glossary\Glossary;

/**
 * Glossary Variable
 *
 * Craft allows plugins to provide their own template variables, accessible from
 * the {{ craft }} global variable (e.g. {{ craft.glossary }}).
 *
 * https://craftcms.com/docs/plugins/variables
 *
 * @author    percipiolondon
 * @package   Glossary
 * @since     1.0.0
 */
class GlossaryVariable implements ViteVariableInterface
{
    // Public Methods
    // =========================================================================

    /**
     * Whatever you want to output to a Twig template can go into a Variable method.
     * You can have as many variable functions as you want.  From any Twig template,
     * call it like this:
     *
     *     {{ craft.glossary.exampleVariable }}
     *
     * Or, if your variable requires parameters from Twig:
     *
     *     {{ craft.glossary.exampleVariable(twigValue) }}
     *
     * @param null $optional
     * @return string
     */

    use ViteVariableTrait;
}
