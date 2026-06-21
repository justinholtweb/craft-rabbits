<?php

namespace justinholtweb\rabbits;

use Craft;
use craft\base\Model;
use craft\base\Plugin as BasePlugin;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\events\RegisterUserPermissionsEvent;
use craft\services\Elements;
use craft\services\Gc;
use craft\services\UserPermissions;
use craft\web\UrlManager;
use justinholtweb\rabbits\elements\Component;
use justinholtweb\rabbits\models\Settings;
use justinholtweb\rabbits\services\AnimationManager;
use justinholtweb\rabbits\services\Builder;
use justinholtweb\rabbits\services\Components;
use justinholtweb\rabbits\services\Renderer;
use justinholtweb\rabbits\services\Runtime;
use justinholtweb\rabbits\services\StyleManager;
use justinholtweb\rabbits\services\ThemeBridge;
use justinholtweb\rabbits\services\TwigCompiler;
use justinholtweb\rabbits\twig\RabbitsExtension;
use yii\base\Event;

/**
 * Rabbits — Webflow-style visual component builder for Craft CMS
 *
 * @property-read Components $components
 * @property-read Builder $builder
 * @property-read TwigCompiler $compiler
 * @property-read StyleManager $styles
 * @property-read AnimationManager $animations
 * @property-read ThemeBridge $themes
 * @property-read Renderer $renderer
 * @property-read Runtime $runtime
 * @method Settings getSettings()
 */
class Plugin extends BasePlugin
{
    public string $schemaVersion = '0.1.0';
    public bool $hasCpSettings = true;
    public bool $hasCpSection = true;

    public static function config(): array
    {
        return [
            'components' => [
                'components' => Components::class,
                'builder' => Builder::class,
                'compiler' => TwigCompiler::class,
                'styles' => StyleManager::class,
                'animations' => AnimationManager::class,
                'themes' => ThemeBridge::class,
                'renderer' => Renderer::class,
                'runtime' => Runtime::class,
            ],
        ];
    }

    public function init(): void
    {
        parent::init();

        $this->_registerElementTypes();
        $this->_registerTwigExtensions();
        $this->_registerGarbageCollection();

        if (Craft::$app->getRequest()->getIsCpRequest()) {
            $this->_registerCpUrlRules();
        }

        if (Craft::$app->getEdition() === Craft::Pro) {
            $this->_registerPermissions();
        }

        Craft::info('Rabbits plugin loaded', __METHOD__);
    }

    public function getCpNavItem(): ?array
    {
        $item = parent::getCpNavItem();

        if ($item === null) {
            return null;
        }

        $item['label'] = 'Rabbits';
        $item['subnav'] = [
            'components' => [
                'label' => Craft::t('rabbits', 'Components'),
                'url' => 'rabbits/components',
            ],
        ];

        if (Craft::$app->getUser()->getIsAdmin() && Craft::$app->getConfig()->getGeneral()->allowAdminChanges) {
            $item['subnav']['settings'] = [
                'label' => Craft::t('rabbits', 'Settings'),
                'url' => 'rabbits/settings',
            ];
        }

        return $item;
    }

    public function getSettingsResponse(): mixed
    {
        return Craft::$app->getResponse()->redirect(
            \craft\helpers\UrlHelper::cpUrl('rabbits/settings')
        );
    }

    protected function createSettingsModel(): ?Model
    {
        return new Settings();
    }

    protected function settingsHtml(): ?string
    {
        return Craft::$app->getView()->renderTemplate('rabbits/_cp/settings/index', [
            'settings' => $this->getSettings(),
        ]);
    }

    private function _registerElementTypes(): void
    {
        Event::on(
            Elements::class,
            Elements::EVENT_REGISTER_ELEMENT_TYPES,
            function (RegisterComponentTypesEvent $event) {
                $event->types[] = Component::class;
            }
        );
    }

    private function _registerTwigExtensions(): void
    {
        Craft::$app->getView()->registerTwigExtension(new RabbitsExtension());
    }

    private function _registerCpUrlRules(): void
    {
        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_CP_URL_RULES,
            function (RegisterUrlRulesEvent $event) {
                // Component CRUD
                $event->rules['rabbits'] = 'rabbits/components/index';
                $event->rules['rabbits/components'] = 'rabbits/components/index';
                $event->rules['rabbits/components/new'] = 'rabbits/components/new';
                $event->rules['rabbits/components/<componentId:\d+>'] = 'rabbits/components/edit';

                // Visual builder
                $event->rules['rabbits/builder/<componentId:\d+>'] = 'rabbits/builder/index';

                // Preview
                $event->rules['rabbits/preview/render'] = 'rabbits/preview/render';

                // Settings
                $event->rules['rabbits/settings'] = 'rabbits/components/settings';
            }
        );
    }

    private function _registerPermissions(): void
    {
        Event::on(
            UserPermissions::class,
            UserPermissions::EVENT_REGISTER_PERMISSIONS,
            function (RegisterUserPermissionsEvent $event) {
                $event->permissions[] = [
                    'heading' => Craft::t('rabbits', 'Rabbits'),
                    'permissions' => [
                        'rabbits:accessPlugin' => [
                            'label' => Craft::t('rabbits', 'Access Rabbits'),
                        ],
                        'rabbits:manageComponents' => [
                            'label' => Craft::t('rabbits', 'Manage Components'),
                        ],
                        'rabbits:manageClasses' => [
                            'label' => Craft::t('rabbits', 'Manage Classes'),
                        ],
                        'rabbits:manageTokens' => [
                            'label' => Craft::t('rabbits', 'Manage Tokens'),
                        ],
                        'rabbits:manageSettings' => [
                            'label' => Craft::t('rabbits', 'Manage Settings'),
                        ],
                    ],
                ];
            }
        );
    }

    private function _registerGarbageCollection(): void
    {
        Event::on(
            Gc::class,
            Gc::EVENT_RUN,
            function () {
                Craft::$app->getGc()->deletePartialElements(
                    Component::class,
                    '{{%rabbits_components}}',
                    'id'
                );
            }
        );
    }
}
