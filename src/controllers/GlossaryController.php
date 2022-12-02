<?php
/**
 * Glossary plugin for Craft CMS 4.x
 *
 * Craft Plugin that synchronises with Glossary
 *
 * @link      https://percipio.london
 * @copyright Copyright (c) 2021 percipiolondon
 */

namespace percipiolondon\glossary\controllers;

use Craft;

use percipiolondon\glossary\records\GlossaryDefinitionRecord;
use percipiolondon\glossary\records\GlossaryRecord;
use yii\web\Response;
use craft\web\Controller;

/**
 * Default Controller
 *
 * Generally speaking, controllers are the middlemen between the front end of
 * the CP/website and your plugin’s services. They contain action methods which
 * handle individual tasks.
 *
 * A common pattern used throughout Craft involves a controller action gathering
 * post data, saving it on a model, passing the model off to a service, and then
 * responding to the request appropriately depending on the service method’s response.
 *
 * Action methods begin with the prefix “action”, followed by a description of what
 * the method does (for example, actionSaveIngredient()).
 *
 * https://craftcms.com/docs/plugins/controllers
 *
 * @author    percipiolondon
 * @package   Glossary
 * @since     1.0.0
 */
class GlossaryController extends Controller
{
    // Protected Properties
    // =========================================================================
    protected array|int|bool $allowAnonymous = [
        'get-glossaries',
        'get-glossary'
    ];

    // Public Methods
    // =========================================================================

    /**
     * Glossary display
     *
     * @return Response The rendered result
     */
    public function actionOverview(): Response
    {
        $variables = [];

        $pluginName = 'Glossary';
        $templateTitle = Craft::t('glossary', 'Glossary');

        $variables['controllerHandle'] = 'glossary';
        $variables['title'] = $templateTitle;
        $variables['docTitle'] = sprintf('%s - %s', $pluginName, $templateTitle);

        // Render the template
        return $this->renderTemplate('glossary/index', $variables);
    }

    public function actionGetGlossaries(int $limit = null, int $offset = null, int $sort = SORT_DESC): Response
    {
        $glossaries = GlossaryRecord::find()
            ->where(['not', ['parentId' => null]])
            ->limit($limit)
            ->offset($offset)
            ->all();

        $glossariesData = [];

        foreach($glossaries as $glossary) {

            $variants = GlossaryRecord::findAll(['parentId' => $glossary->id]);
            $definitions = GlossaryDefinitionRecord::findAll(['glossaryId' => $glossary->id]);

            $data = [];
            $data['id'] = $glossary->id;
            $data['term'] = $glossary->term;
            $data['variants'] = $variants;
            $data['definitions'] = $definitions;

            $glossariesData[] = $data;
        }

        return $this->asJson([
            'success' => true,
            'glossary' => $glossariesData,
            'total' => GlossaryRecord::find()->count()
        ]);
    }

    public function actionGetGlossary(int $id): Response
    {
        return $this->asJson([
            'success' => true,
            'term' => GlossaryRecord::findOne($id),
            'variants' => GlossaryRecord::find()
                ->where(['parentId' => $id])
                ->all(),
            'definitions' => GlossaryDefinitionRecord::findAll(['glossaryId' => $id]),
        ]);
    }

    public function actionEdit(int $glossaryId = null): Response
    {
        $variables = [];

        $pluginName = 'Glossary';
        $templateTitle = Craft::t('glossary', 'Glossary');

        $variables['controllerHandle'] = 'glossary';
        $variables['title'] = $templateTitle;
        $variables['docTitle'] = sprintf('%s - %s', $pluginName, $templateTitle);

        $variables['glossary'] = null;

        if ($glossaryId) {
            $variables['glossary'] = GlossaryRecord::findOne($glossaryId);
        }

        // Render the template
        return $this->renderTemplate('glossary/form', $variables);
    }

    public function actionDelete(int $glossaryId = null): Response
    {
        if ($glossaryId) {
            $glossary = GlossaryRecord::findOne($glossaryId);

            if (!is_null($glossary)) {
                $glossary->delete();
            }
        }

        // Render the template
        return $this->redirect('/' . Craft::$app->request->generalConfig->cpTrigger . '/glossary');
    }

    public function actionSave(): Response
    {
        $this->requirePostRequest();

        $success = false;

        $request = Craft::$app->getRequest();

        $id = $request->getBodyParam('id');
        $term = $request->getBodyParam('term');
        $variants = $request->getBodyParam('termVariants');
        $definitions = $request->getBodyParam('definition');
        $siteId = $request->getBodyParam('siteId');

        $errors = [];

        // save glossary
        if ($id) {
            $glossary = GlossaryRecord::findOne($id);
        } else {
            $glossary = new GlossaryRecord();
        }

        $glossary->term = strtolower($term);
        $glossary->siteId = $siteId;
        $success = $glossary->save();

        if (!$success) {
            $errors[] = $glossary->errors;
        }


        /* VARIANTS */
        // get all variants to reduce the list and the ones left needs to be deleted
        $variantsToDelete = GlossaryRecord::find()
            ->where(['parentId' => $glossary->id])
            ->all();

        // save variants
        foreach($variants as $variant) {

            // remove variant from variants to delete
            $variantsToDelete = array_filter($variantsToDelete, function($var) use ($variant) {
                return $var->term !== $variant;
            });

            // fetch variant if existing
            $termRecord = GlossaryRecord::find()
                ->where(['parentId' => $glossary->id, 'term' => $variant])
                ->one();

            // save current glossary
            if ( is_null($termRecord)) {
                $termRecord = new GlossaryRecord();
            }

            $termRecord->term = strtolower($variant);
            $termRecord->parentId = $glossary->id;
            $termRecord->siteId = $siteId;
            $success = $termRecord->save();

            if (!$success) {
                $errors[] = $termRecord->errors;
            }
        }

        // delete variants if existing
        foreach ($variantsToDelete as $deleteVariant) {
            $deleteVariant->delete();
        }


        /* DEFINITIONS */
        // get all variants to reduce the list and the ones left needs to be deleted
        $definitionsToDelete = GlossaryDefinitionRecord::find()
            ->where(['glossaryId' => $glossary->id])
            ->all();

        // save definitions
        foreach($definitions as $definition) {

            // remove variant from variants to delete
            $definitionsToDelete = array_filter($definitionsToDelete, function($def) use ($definition) {
                return $def['id'] !== $definition['id'];
            });

            // get difinition
            $definitionRecord = GlossaryDefinitionRecord::findOne($definition['id']);

            if (is_null($definitionRecord)) {
                $definitionRecord = new GlossaryDefinitionRecord();
            }
            $definitionRecord->definition = $definition['definition'];
            $definitionRecord->glossaryId = $glossary->id;

            if ($definition['exposure'] !== 'all') {
                $definitionRecord->sectionHandle = $definition['exposure'];
            }

            $success = $definitionRecord->save();

            if (!$success) {
                $errors[] = $definitionRecord->errors;
            }
        }

        // delete definitions if existing
        foreach ($definitionsToDelete as $deleteDefinition) {
            $deleteDefinition->delete();
        }

        return $this->asJson([
            'success' => $success,
            'errors' => $errors
        ]);

//        if (GlossaryRecord::findOne(['term' => $term])) {
//            $glossary->addError('term', 'This term already exists');
//        }

//        if ($success) {
//            return $this->redirect('/' . Craft::$app->request->generalConfig->cpTrigger . '/glossary');
//        }

//        $glossary->save();
//
//        $pluginName = 'Glossary';
//        $templateTitle = Craft::t('glossary', 'Glossary');
//
//        $variables['controllerHandle'] = 'glossary';
//        $variables['title'] = $templateTitle;
//        $variables['docTitle'] = sprintf('%s - %s', $pluginName, $templateTitle);
//        $variables['glossary'] = $glossary;
//        $variables['errors'] = $glossary->getErrors();
//
//        // Render the template
//        return $this->renderTemplate('glossary/form', $variables);
    }


}
