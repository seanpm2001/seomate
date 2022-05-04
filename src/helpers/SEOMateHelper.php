<?php
/**
 * SEOMate plugin for Craft CMS 3.x
 *
 * @link      https://www.vaersaagod.no/
 * @copyright Copyright (c) 2019 Værsågod
 */

namespace vaersaagod\seomate\helpers;

use Craft;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\elements\Asset;
use craft\elements\db\ElementQuery;
use craft\elements\db\MatrixBlockQuery;
use craft\elements\MatrixBlock;
use craft\errors\SiteNotFoundException;
use craft\helpers\UrlHelper;
use vaersaagod\seomate\models\Settings;
use vaersaagod\seomate\SEOMate;

/**
 * SEOMate Helper
 *
 * @author    Værsågod
 * @package   SEOMate
 * @since     1.0.0
 */
class SEOMateHelper
{
    /**
     * Updates Settings model wit override values
     */
    public static function updateSettings(Settings $settings, array $overrides): void
    {
        foreach ($overrides as $key => $val) {
            $settings[$key] = $val;
        }
    }

    /**
     * Gets the profile to use from element and settings
     */
    public static function getElementProfile(Element $element, Settings $settings): mixed
    {
        if (!isset($settings->profileMap) || !\is_array($settings->profileMap)) {
            return null;
        }

        $fieldMap = self::expandMap($settings->profileMap);
        $mapIds = [];

        if (method_exists($element, 'refHandle')) {
            $refHandle = strtolower($element->refHandle());

            if ($refHandle == 'entry') {
                $mapIds[] = $element->section->handle;
            } elseif ($refHandle == 'category') {
                $mapIds[] = $element->group->handle;
            }
        }

        if (\count($mapIds) === 0) {
            return null;
        }

        foreach ($mapIds as $mapId) {
            if (isset($fieldMap[$mapId])) {
                return $fieldMap[$mapId];
            }
        }

        return null;
    }

    /**
     * Returns the meta type from key
     */
    public static function getMetaTypeByKey(string $key): string
    {
        $settings = SEOMate::$plugin->getSettings();
        $typeMap = self::expandMap($settings->metaPropertyTypes);

        if (isset($typeMap[$key])) {
            if (\is_array($typeMap[$key])) {
                return $typeMap[$key]['type'];
            }

            return $typeMap[$key];
        }

        return 'text';
    }

    /**
     * Reduces a nested scope to the deepest possible target scope, and return it and
     * the remaining handle.
     */
    public static function reduceScopeAndHandle(array $scope, string $handle): array
    {
        if (strrpos($handle, '.') === false) {
            return [$scope, $handle];
        }

        $currentScope = null;
        $handleParts = explode('.', $handle);
        $first = true; // a wee bit ugly, but it's to avoid that a wrong target is reached if one part is null.

        for ($i = 0, $iMax = count($handleParts) - 1; $i < $iMax; ++$i) {
            $part = $handleParts[$i];

            if (strrpos($part, ':') !== false) {
                return [$currentScope, implode('.', array_slice($handleParts, $i))];
            }

            if ($first) {
                $currentScope = $scope[$part] ?? null;
            } elseif ($currentScope !== null) {
                $currentScope = $currentScope[$part] ?? null;
            }
        }

        return [$currentScope, $handleParts[count($handleParts) - 1]];
    }

    public static function getPropertyDataByScopeAndHandle(ElementInterface|array $scope, string $handle, string $type): Asset|string|null
    {
        if ($scope[$handle] ?? null) { // Root field
            if ($type === 'text') {
                if (($value = \trim(\strip_tags((string)($scope[$handle] ?? '')))) !== '' && ($value = \trim(\strip_tags((string)($scope[$handle] ?? '')))) !== '0') {
                    return $value;
                }
            } elseif ($type === 'image') {
                $elements = $scope[$handle];
                $assets = ($elements instanceof ElementQuery) ? $elements->all() : $elements;

                if (is_array($assets) && !empty($assets)) {
                    foreach ($assets as $asset) {
                        if (self::isValidImageAsset($asset)) {
                            return $asset;
                        }
                    }
                }
            }
        } elseif (\strpos($handle, ':')) {

            // Assume Matrix field, in the config format $fieldHandle:$blockTypeHandle.$fieldHandle
            // First, get the Matrix field's handle, and test if that attribute actually is a MatrixBlockQuery instance
            $matrixFieldPathSegments = \explode(':', $handle);
            $handle = \array_shift($matrixFieldPathSegments) ?: null;
            if (!$handle || empty($matrixFieldPathSegments) || !($scope[$handle] ?? null) || !($scope[$handle] instanceof MatrixBlockQuery)) {
                return null;
            }

            // Nice one, there's actually a Matrix field for that attribute.
            // Now get the block type and field handles
            $blockPathSegments = \explode('.', $matrixFieldPathSegments[0]);
            if (!($blockTypeHandle = $blockPathSegments[0] ?? null) || !($blockFieldHandle = $blockPathSegments[1] ?? null)) {
                return null;
            }

            $blockQuery = clone $scope[$handle];

            if ($type === 'text') {
                $blocks = $blockQuery->all();

                foreach ($blocks as $block) {
                    if ($block->getType()->handle !== $blockTypeHandle) {
                        continue;
                    }

                    $value = \trim(\strip_tags((string)($block[$blockFieldHandle] ?? '')));

                    if ($value !== '' && $value !== '0') {
                        return $value;
                    }
                }
            } elseif ($type === 'image') {
                if (Craft::$app->getRequest()->getIsLivePreview()) {
                    $blocks = $blockQuery->all();
                } else {
                    $blocks = $blockQuery->with([sprintf('%s:%s', $blockTypeHandle, $blockFieldHandle)])->all();
                }

                /* @var MatrixBlock $block */
                foreach ($blocks as $block) {
                    if ($block->type->handle !== $blockTypeHandle || !($assets = $block[$blockFieldHandle] ?? null)) {
                        continue;
                    }

                    if ($assets instanceof ElementQuery) {
                        $assets = $assets->all();
                    }

                    if (!$assets || !\is_array($assets)) {
                        continue;
                    }
                    
                    foreach ($assets as $asset) {
                        if (self::isValidImageAsset($asset)) {
                            return $asset;
                        }
                    }
                }
            }
        }

        return null;
    }

    /**
     * Checks if give Asset is in the list of $settings->validImageExtensions
     *
     *
     */
    public static function isValidImageAsset(Asset $asset): bool
    {
        $settings = SEOMate::$plugin->getSettings();
        return \in_array(strtolower($asset->getExtension()), $settings->validImageExtensions, true);
    }

    /**
     * Expands config setting map where key is exandable
     */
    public static function expandMap(array $map): array
    {
        $r = [];

        foreach ($map as $k => $v) {
            $keys = explode(',', $k);

            foreach ($keys as $key) {
                $r[trim($key)] = $v;
            }
        }

        return $r;
    }

    /**
     * Checks if array is associative
     */
    public static function isAssocArray(array $array): bool
    {
        if (array() === $array) {
            return false;
        }

        return array_keys($array) !== range(0, \count($array) - 1);
    }

    /**
     * Renders a string template with context
     */
    public static function renderString(string $string, array $context): string
    {
        try {
            return Craft::$app->getView()->renderString($string, $context);
        } catch (\Throwable $throwable) {
            Craft::error($throwable->getMessage(), __METHOD__);
        }

        return '';
    }

    /**
     * @throws SiteNotFoundException
     */
    public static function ensureAbsoluteUrl(string $url): string
    {
        if (UrlHelper::isAbsoluteUrl($url)) {
            return $url;
        }

        // Get the base url and assume it's what we want to use
        $siteUrl = UrlHelper::baseSiteUrl();
        $siteUrlParts = parse_url($siteUrl);
        $scheme = $siteUrlParts['scheme'] ?? (Craft::$app->getRequest()->isSecureConnection ? 'https' : 'http');

        if (UrlHelper::isProtocolRelativeUrl($url)) {
            return UrlHelper::urlWithScheme($url, $scheme);
        }

        if (str_starts_with($url, '/')) {
            return $scheme . '://' . $siteUrlParts['host'] . $url;
        }

        // huh, relative url? Seems unlikely, but... If we've come this far.
        return $scheme . '://' . $siteUrlParts['host'] . '/' . $url;
    }
}
