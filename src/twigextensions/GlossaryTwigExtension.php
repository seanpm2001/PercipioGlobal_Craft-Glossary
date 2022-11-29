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
        $glossaries = GlossaryRecord::find()
            ->orderBy(['term' => SORT_ASC])
            ->all();
        $view = \Craft::$app->getView();
        $termTemplate = '<span class="glossary-term">{{ text }}</span>';

        foreach ($glossaries as $glossary) {
            // https://stackoverflow.com/questions/20767089/preg-replace-when-not-inside-double-quotes
            $pattern = '/\b'.$glossary->term.'\b(?![^"]*"(?:(?:[^"]*"){2})*[^"]*$)/i';
            $replacement = '<span class="glossary"><span class="glossary-term">${1}</span><span class="glossary-definition">'.$glossary->definition.'</span></span>';
            $text = preg_replace_callback(
                $pattern,
                function ($matches) use ($glossary){
                    return '<span class="glossary"><span class="glossary-term">'.$matches[0].'</span><span class="glossary-definition">'.$glossary->definition.'</span></span>&nbsp;';
                },
                $text
            );
        }

        return $text;
    }

    public function getGlossary($term): ?string
    {
        $glossary = GlossaryRecord::findOne(['term' => strtolower($term)]);

        if (!is_null($glossary)) {
            return $glossary->definition;
        }

        return null;
    }
}
