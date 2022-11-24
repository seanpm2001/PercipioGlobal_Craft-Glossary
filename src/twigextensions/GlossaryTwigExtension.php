<?php
/**
 * Glossary plugin for Craft CMS 3.x
 *
 * Create a glossary of terms
 *
 * @link      https://percipio.london
 * @copyright Copyright (c) 2022 percipiolondon
 */

namespace percipiolondon\glossary\twigextensions;

use percipiolondon\glossary\records\GlossaryRecord;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * Twig can be extended in many ways; you can add extra tags, filters, tests, operators,
 * global variables, and functions. You can even extend the parser itself with
 * node visitors.
 *
 * http://twig.sensiolabs.org/doc/advanced.html
 *
 * @author    percipiolondon
 * @package   Glossary
 * @since     1.0.0
 */
class GlossaryTwigExtension extends AbstractExtension
{
    // Public Methods
    // =========================================================================

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'Glossary';
    }

    /**
     * Returns an array of Twig filters, used in Twig templates via:
     *
     *      {{ 'something' | someFilter }}
     *
     * @return array
     */
    public function getFilters()
    {
        return [
            new TwigFilter('glossary', [$this, 'glossary'], ['is_safe' => ['html']]),
        ];
    }

    /**
     * Returns an array of Twig functions, used in Twig templates via:
     *
     *      {% set this = someFunction('something') %}
     *
    * @return array
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('getGlossary', array($this, 'getGlossary')),
        ];
    }

    /**
     * Our function called via Twig; it can do anything you want
     *
     * @param null $text
     *
     * @return string
     */
    public function glossary($text): string
    {
        $glossaries = GlossaryRecord::find()->all();

        foreach ($glossaries as $glossary) {
            $text = preg_replace("/\b(".$glossary->term.")\b/i", '<span class="glossary"><span class="glossary-term">$1</span><span class="glossary-definition">'.$glossary->definition.'</span></span>', $text);
        }

        return $text;
    }

    public function getGlossary($term): string
    {
        $glossary = GlossaryRecord::findOne(['term' => $term]);

        return $glossary->definition;
    }
}
