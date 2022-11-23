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
    protected array|int|bool $allowAnonymous = [];

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

    public function actionGetGlossary(int $limit = null, int $offset = null): Response
    {
        return $this->asJson([
            'success' => true,
            'glossary' => GlossaryRecord::find()
                ->limit($limit)
                ->offset($offset)
                ->all(),
            'total' => GlossaryRecord::find()->count()
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
        $id = $request->getBodyParam('glossaryId');
        $term = $request->getBodyParam('term');
        $definition = $request->getBodyParam('definition');
        $siteId = $request->getBodyParam('siteId');

        if ($id) {
            $glossary = GlossaryRecord::findOne($id);
        } else {
            $glossary = new GlossaryRecord();
        }

        $glossary->term = $term;
        $glossary->definition = $definition;
        $glossary->siteId = $siteId;

        if (GlossaryRecord::findOne(['term' => $term])) {
            $glossary->addError('term', 'This term already exists');
        }

        if ($glossary->validate()) {
            $success = $glossary->save();
        }

        if ($success) {
            return $this->redirect('/' . Craft::$app->request->generalConfig->cpTrigger . '/glossary');
        }

        $glossary->save();

        $pluginName = 'Glossary';
        $templateTitle = Craft::t('glossary', 'Glossary');

        $variables['controllerHandle'] = 'glossary';
        $variables['title'] = $templateTitle;
        $variables['docTitle'] = sprintf('%s - %s', $pluginName, $templateTitle);
        $variables['glossary'] = $glossary;
        $variables['errors'] = $glossary->getErrors();

        // Render the template
        return $this->renderTemplate('glossary/form', $variables);
    }


}
