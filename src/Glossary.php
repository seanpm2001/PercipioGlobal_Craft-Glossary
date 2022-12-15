<?php
/**
 * Glossary plugin for Craft CMS 3.x
 *
 * Create a glossary of terms
 *
 * @link      https://percipio.london
 * @copyright Copyright (c) 2022 percipiolondon
 */

namespace percipiolondon\glossary;

use percipiolondon\glossary\services\GlossaryService;
use percipiolondon\glossary\twigextensions\GlossaryTwigExtension;
use percipiolondon\glossary\variables\GlossaryVariable;
use percipiolondon\glossary\assetbundles\glossary\GlossaryAsset;

use Craft;
use craft\base\Plugin;
use craft\services\Plugins;
use craft\events\PluginEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\web\twig\variables\CraftVariable;
use craft\web\UrlManager;
use nystudio107\pluginvite\services\VitePluginService;
use craft\services\Gql;
use craft\events\RegisterGqlDirectivesEvent;
use percipiolondon\glossary\gql\directives\Glossary as GlossaryDirective;

use yii\base\Event;

/**
 * Craft plugins are very much like little applications in and of themselves. We’ve made
 * it as simple as we can, but the training wheels are off. A little prior knowledge is
 * going to be required to write a plugin.
 *
 * For the purposes of the plugin docs, we’re going to assume that you know PHP and SQL,
 * as well as some semi-advanced concepts like object-oriented programming and PHP namespaces.
 *
 * https://docs.craftcms.com/v3/extend/
 *
 * @author    percipiolondon
 * @package   Glossary
 * @since     1.0.0
 *
 * @property GlossaryService $glossaryService
 *
 */
class Glossary extends Plugin
{
    // Static Properties
    // =========================================================================

    /**
     * Static property that is an instance of this plugin class so that it can be accessed via
     * Glossary::$plugin
     *
     * @var Glossary
     */
    public static Glossary $plugin;

    /**
     * @var GlossaryVariable|null
     */
    public static ?GlossaryVariable $glossaryVariable = null;

    // Public Properties
    // =========================================================================

    /**
     * To execute your plugin’s migrations, you’ll need to increase its schema version.
     *
     * @var string
     */
    public string $schemaVersion = '1.0.0';

    /**
     * Set to `true` if the plugin should have a settings view in the control panel.
     *
     * @var bool
     */
    public bool $hasCpSettings = false;

    /**
     * Set to `true` if the plugin should have its own section (main nav item) in the control panel.
     *
     * @var bool
     */
    public bool $hasCpSection = true;

    // Public Methods
    // =========================================================================

    /**
     * Set our $plugin static property to this class so that it can be accessed via
     * Glossary::$plugin
     *
     * Called after the plugin class is instantiated; do any one-time initialization
     * here such as hooks and events.
     *
     * If you have a '/vendor/autoload.php' file, it will be loaded for you automatically;
     * you do not need to load it in your init() method.
     *
     */
    public function init()
    {
        parent::init();
        self::$plugin = $this;

        $this->setComponents([
            'glossaryService' => GlossaryService::class,
            'vite' => [
                'class' => VitePluginService::class,
                'assetClass' => GlossaryAsset::class,
                'useDevServer' => true,
                'devServerPublic' => 'http://localhost:3753',
                'serverPublic' => 'http://localhost:3700',
                'errorEntry' => '/src/js/main.ts',
                'devServerInternal' => 'http://craft-glossary-buildchain:3753',
                'checkDevServer' => true,
            ],
        ]);

        // Register variables
        Event::on(
            CraftVariable::class,
            CraftVariable::EVENT_INIT,
            function(Event $event): void {
                /** @var CraftVariable $variable */
                $variable = $event->sender;
                $variable->set('glossary', [
                    'class' => GlossaryVariable::class,
                    'viteService' => $this->vite,
                ]);
            }
        );

        // Add in our Twig extensions
        Craft::$app->view->registerTwigExtension(new GlossaryTwigExtension());

        // Do something after we're installed
        Event::on(
            Plugins::class,
            Plugins::EVENT_AFTER_INSTALL_PLUGIN,
            function (PluginEvent $event) {
                if ($event->plugin === $this) {
                    // We were just installed
                }
            }
        );

        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_CP_URL_RULES,
            function (RegisterUrlRulesEvent $event) {
                Craft::debug(
                    'UrlManager::EVENT_REGISTER_CP_URL_RULES',
                    __METHOD__
                );
                // Register our control panel routes
                $event->rules = array_merge(
                    $event->rules,
                    $this->customAdminCpRoutes()
                );
            }
        );

        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_SITE_URL_RULES,
            function (RegisterUrlRulesEvent $event) {
                Craft::debug(
                    'UrlManager::EVENT_REGISTER_SITE_URL_RULES',
                    __METHOD__
                );
                // Register our control panel routes
                $event->rules = array_merge(
                    $event->rules,
                    $this->customSiteRoutes()
                );
            }
        );

        Event::on(
            Gql::class,
            Gql::EVENT_REGISTER_GQL_DIRECTIVES,
            function (RegisterGqlDirectivesEvent $event) {
                $event->directives[] = GlossaryDirective::class;
            }
        );

        Craft::info(
            Craft::t(
                'glossary-of-terms',
                '{name} plugin loaded',
                ['name' => $this->name]
            ),
            __METHOD__
        );
    }

    // Protected Methods
    // =========================================================================
    protected function customAdminCpRoutes(): array
    {
        return [
            'glossary-of-terms' => 'glossary-of-terms/glossary/overview',
            'glossary-of-terms/edit' => 'glossary-of-terms/glossary/edit',
            'glossary-of-terms/delete' => 'glossary-of-terms/glossary/delete',
            'glossary-of-terms/add' => 'glossary-of-terms/glossary/edit',
        ];
    }

    // Protected Methods
    // =========================================================================
    protected function customSiteRoutes(): array
    {
        return [
            'glossary-of-terms/get-glossaries' => 'glossary-of-terms/glossary/get-glossaries',
            'glossary-of-terms/get-glossary' => 'glossary-of-terms/glossary/get-glossary',
        ];
    }
}
