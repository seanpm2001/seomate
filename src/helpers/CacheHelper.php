<?php
/**
 * SEOMate plugin for Craft CMS 3.x
 *
 * @link      https://www.vaersaagod.no/
 * @copyright Copyright (c) 2018 Værsågod
 */

namespace vaersaagod\seomate\helpers;

use craft\helpers\UrlHelper;
use vaersaagod\seomate\SEOMate;

use Craft;
use yii\caching\TagDependency;

/**
 * SEOMate Helper
 *
 * @author    Værsågod
 * @package   SEOMate
 * @since     1.0.0
 */
class CacheHelper
{
    const SEOMATE_TAG = 'seomate_tag';
    const ELEMENT_TAG = 'seomate_meta_element_tag';
    const ELEMENT_KEY_PREFIX = 'seomate_meta_element';

    public static function clearAllCaches()
    {
        $cache = Craft::$app->getCache();
        TagDependency::invalidate($cache, self::SEOMATE_TAG);
    }

    public static function hasMetaCacheForElement($element)
    {
        $cache = Craft::$app->getCache();
        return $cache->get(self::getElementKey($element)) ? true : false;
    }

    public static function getMetaCacheForElement($element)
    {
        $cache = Craft::$app->getCache();
        return $cache->get(self::getElementKey($element));
    }

    public static function deleteMetaCacheForElement($element)
    {
        $cache = Craft::$app->getCache();
        $cache->delete(self::getElementKey($element));
    }

    public static function setMetaCacheForElement($element, $meta)
    {
        $settings = SEOMate::$plugin->getSettings();

        $cache = Craft::$app->getCache();
        $cacheDuration = $settings->cacheDuration;

        $dependency = new TagDependency([
            'tags' => [
                self::SEOMATE_TAG,
                self::ELEMENT_TAG,
            ],
        ]);

        $cache->set(self::getElementKey($element), $meta, $cacheDuration, $dependency);
    }

    private static function getElementKey($element): string
    {
        return self::ELEMENT_KEY_PREFIX . '_' . $element->site->handle . '_' . $element->id;
    }

}