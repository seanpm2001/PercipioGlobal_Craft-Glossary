<?php

namespace percipiolondon\glossary\services;

use craft\base\Component;
use percipiolondon\glossary\records\GlossaryDefinitionRecord;
use percipiolondon\glossary\records\GlossaryRecord;


class GlossaryService extends Component
{
    public array $matches = [];

    public function renderGlossary(string $text, string|null $handle = null): string
    {
        $glossaries = GlossaryRecord::find()
            ->orderBy(['term' => SORT_DESC])
            ->all();

        $this->matches = [];

        foreach ($glossaries as $glossary) {
            // https://stackoverflow.com/questions/20767089/preg-replace-when-not-inside-double-quotes
            if ($glossary->parentId) {
                if ($handle) {
                    $definition = GlossaryDefinitionRecord::findOne(['glossaryId' => $glossary->parentId, 'sectionHandle' => $handle]);
                } else {
                    $definition = GlossaryDefinitionRecord::find()
                        ->where( ['glossaryId' => $glossary->parentId])
                        ->andWhere('sectionHandle IS NULL')
                        ->one();
                }

                // fallback if there was no result for the handle
                if (is_null($definition)) {
                    $definition = GlossaryDefinitionRecord::find()
                        ->where( ['glossaryId' => $glossary->parentId])
                        ->andWhere('sectionHandle IS NULL')
                        ->one();
                }
            } else {
                if ($handle) {
                    $definition = GlossaryDefinitionRecord::findOne(['glossaryId' => $glossary->id, 'sectionHandle' => $handle]);
                } else {
                    $definition = GlossaryDefinitionRecord::find()
                        ->where( ['glossaryId' => $glossary->id])
                        ->andWhere('sectionHandle IS NULL')
                        ->one();
                }

                // fallback if there was no result for the handle
                if (is_null($definition)) {
                    $definition = GlossaryDefinitionRecord::find()
                        ->where( ['glossaryId' => $glossary->id])
                        ->andWhere('sectionHandle IS NULL')
                        ->one();
                }
            }

            if ($definition) {
                $pattern = '/\b'.$glossary->term.'\b(?![^"]*"(?:(?:[^"]*"){2})*[^"]*$)/i';

                $text = preg_replace_callback(
                    $pattern,
                    function ($matches) use ($definition){

                        $keyed = uniqid('', true);
                        $this->matches[$keyed] = [
                            'glossary' => $matches[0],
                            'definition' => $definition->definition
                        ];

                        return $keyed;
                    },
                    $text
                );
            }
        }

//      ALTERNATIVE WAY TO NOT CHANGE INTO QUOTES
//        foreach ($this->matches as $key => $glossary) {
//            $quotes = [];
//            preg_match_all('/"/', explode($key, $content)[0], $quotes);
//            \Craft::dd(explode($key, $content));
////            \Craft::dd(count($quotes) % 2 === 0);
//        }

        foreach ($this->matches as $key => $glossary) {

            $pattern = '/\b'.$key.'\b/i';
            $text = preg_replace_callback(
                $pattern,
                function () use ($glossary){
                    return '<span class="glossary"><span class="glossary-term">'.$glossary['glossary'].'</span><span class="glossary-definition">'.$glossary['definition'].'</span></span>';
                },
                $text
            );
        }

        return $text;
    }

    public function getGlossaryDefinition(string $term, string|null $handle = null): string|null
    {
        $glossary = GlossaryRecord::findOne(['term' => strtolower($term)]);
        $definition = null;

        if ($glossary) {
            if ($glossary->parentId) {
                $definition = GlossaryDefinitionRecord::findOne(['glossaryId' => $glossary->parentId, 'sectionHandle' => $handle]);
            } else {
                $definition = GlossaryDefinitionRecord::findOne(['glossaryId' => $glossary->id, 'sectionHandle' => $handle]);
            }
        }

        if ($definition) {
            return $definition->definition;
        }

        return null;
    }
}
