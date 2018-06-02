<?php
/**
 * SEOMate plugin for Craft CMS 3.x
 *
 * -
 *
 * @link      https://www.vaersaagod.no/
 * @copyright Copyright (c) 2018 Værsågod
 */

namespace vaersaagod\seomate;

use craft\web\View;
use vaersaagod\seomate\services\SEOMateMetaService;
use vaersaagod\seomate\services\SEOMateService as SEOMateServiceService;
use vaersaagod\seomate\variables\SchemaVariable;
use vaersaagod\seomate\variables\SEOMateVariable;
use vaersaagod\seomate\twigextensions\SEOMateTwigExtension;
use vaersaagod\seomate\models\Settings;
use vaersaagod\seomate\fields\SEOMateField as SEOMateFieldField;
use vaersaagod\seomate\utilities\SEOMateUtility as SEOMateUtilityUtility;

use Craft;
use craft\base\Plugin;
use craft\services\Plugins;
use craft\events\PluginEvent;
use craft\web\UrlManager;
use craft\services\Fields;
use craft\services\Utilities;
use craft\web\twig\variables\CraftVariable;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterUrlRulesEvent;

use yii\base\Event;

/**
 * Craft plugins are very much like little applications in and of themselves. We’ve made
 * it as simple as we can, but the training wheels are off. A little prior knowledge is
 * going to be required to write a plugin.
 *
 * For the purposes of the plugin docs, we’re going to assume that you know PHP and SQL,
 * as well as some semi-advanced concepts like object-oriented programming and PHP namespaces.
 *
 * https://craftcms.com/docs/plugins/introduction
 *
 * @author    Værsågod
 * @package   SEOMate
 * @since     1.0.0
 *
 * @property  SEOMateMetaService $meta
 * @property  Settings $settings
 * @method    Settings getSettings()
 */
class SEOMate extends Plugin
{
    // Static Properties
    // =========================================================================

    /**
     * Static property that is an instance of this plugin class so that it can be accessed via
     * SEOMate::$plugin
     *
     * @var SEOMate
     */
    public static $plugin;

    // Public Properties
    // =========================================================================

    /**
     * To execute your plugin’s migrations, you’ll need to increase its schema version.
     *
     * @var string
     */
    public $schemaVersion = '1.0.0';

    // Public Methods
    // =========================================================================

    /**
     * Set our $plugin static property to this class so that it can be accessed via
     * SEOMate::$plugin
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

        // Register services
        $this->setComponents([
            'meta' => SEOMateMetaService::class,
        ]);

        Event::on(
            CraftVariable::class,
            CraftVariable::EVENT_INIT,
            function (Event $event) {
                /** @var CraftVariable $variable */
                $variable = $event->sender;
                $variable->set('schema', SchemaVariable::class);
                $variable->set('seomate', SEOMateVariable::class);
            }
        );

        // Template Hook
        Craft::$app->view->hook(
            'seomateMeta',
            [$this, 'onRegisterMetaHook']
        );

        /*
        // Add in our Twig extensions
        Craft::$app->view->registerTwigExtension(new SEOMateTwigExtension());

        // Register our site routes
        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_SITE_URL_RULES,
            function (RegisterUrlRulesEvent $event) {
                $event->rules['siteActionTrigger1'] = 'seomate/default';
            }
        );

        // Register our CP routes
        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_CP_URL_RULES,
            function (RegisterUrlRulesEvent $event) {
                $event->rules['cpActionTrigger1'] = 'seomate/default/do-something';
            }
        );

        // Register our fields
        Event::on(
            Fields::class,
            Fields::EVENT_REGISTER_FIELD_TYPES,
            function (RegisterComponentTypesEvent $event) {
                $event->types[] = SEOMateFieldField::class;
            }
        );

        // Register our utilities
        Event::on(
            Utilities::class,
            Utilities::EVENT_REGISTER_UTILITY_TYPES,
            function (RegisterComponentTypesEvent $event) {
                $event->types[] = SEOMateUtilityUtility::class;
            }
        );

        // Register our variables
        Event::on(
            CraftVariable::class,
            CraftVariable::EVENT_INIT,
            function (Event $event) {
                $variable = $event->sender;
                $variable->set('seomate', SEOMateVariable::class);
            }
        );

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
        
        */

    }

    /**
     * @param $context
     *
     * @return string
     * @throws \Twig_Error_Loader
     * @throws \yii\base\Exception
     */
    public function onRegisterMetaHook(&$context)
    {
        $craft = \Craft::$app;
        $settings = $this->getSettings();
        
        $meta = $this->meta->getContextMeta($context);
        $alternateUrls = $this->meta->getAlternateUrls($context);

        $context['seomate']['meta'] = $meta;
        $context['seomate']['alternateUrls'] = $alternateUrls;

        if ($settings['metaTemplate'] !== '') {
            return $craft->view->renderTemplate(
                $settings['metaTemplate'],
                $context
            );
        }

        $oldTemplateMode = $craft->getView()->getTemplateMode();
        $craft->getView()->setTemplateMode(View::TEMPLATE_MODE_CP);
        $output = $craft->getView()->renderTemplate(
            'seomate/_output/meta',
            $context
        );
        $craft->getView()->setTemplateMode($oldTemplateMode);

        return $output;
    }

    // Protected Methods
    // =========================================================================

    /**
     * Creates and returns the model used to store the plugin’s settings.
     *
     * @return \craft\base\Model|null
     */
    protected function createSettingsModel()
    {
        return new Settings();
    }

    /**
     * Returns the rendered settings HTML, which will be inserted into the content
     * block on the settings page.
     *
     * @return string The rendered settings HTML
     */
    protected function settingsHtml(): string
    {
        return Craft::$app->view->renderTemplate(
            'seomate/settings',
            [
                'settings' => $this->getSettings()
            ]
        );
    }
}
