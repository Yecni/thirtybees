<?php
/**
 * 2007-2016 PrestaShop
 *
 * thirty bees is an extension to the PrestaShop e-commerce software developed by PrestaShop SA
 * Copyright (C) 2017-2018 thirty bees
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@thirtybees.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://www.thirtybees.com for more information.
 *
 * @author    thirty bees <contact@thirtybees.com>
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2017-2018 thirty bees
 * @copyright 2007-2016 PrestaShop SA
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  PrestaShop is an internationally registered trademark & property of PrestaShop SA
 */

/**
 * Class ImageTypeCore
 *
 * @since 1.0.0
 */
class ImageTypeCore extends ObjectModel
{
    // @codingStandardsIgnoreStart
    /** @var string Name */
    public $name;
    /** @var int Width */
    public $width;
    /** @var int Height */
    public $height;
    /** @var bool Apply to products */
    public $products;
    /** @var int Apply to categories */
    public $categories;
    /** @var int Apply to manufacturers */
    public $manufacturers;
    /** @var int Apply to suppliers */
    public $suppliers;
    /** @var int Apply to scenes */
    public $scenes;
    /** @var int Apply to store */
    public $stores;
    // @codingStandardsIgnoreEnd

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table'   => 'image_type',
        'primary' => 'id_image_type',
        'fields'  => [
            'name'          => ['type' => self::TYPE_STRING, 'validate' => 'isImageTypeName', 'required' => true, 'size' => 64],
            'width'         => ['type' => self::TYPE_INT, 'validate' => 'isImageSize', 'required' => true],
            'height'        => ['type' => self::TYPE_INT, 'validate' => 'isImageSize', 'required' => true],
            'categories'    => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'products'      => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'manufacturers' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'suppliers'     => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'scenes'        => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'stores'        => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
        ],
    ];

    protected $webserviceParameters = [];

    /**
     * Returns image type definitions
     *
     * @param string|null $type Image type
     * @param bool        $orderBySize
     *
     * @return array Image type definitions
     * @throws PrestaShopDatabaseException
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public static function getImagesTypes($type = null, $orderBySize = false)
    {
        static $cache = [];

        if ( ! isset($cache[$type])) {
            $query = (new DbQuery())
                ->select('*')
                ->from('image_type');
            if (!empty($type)) {
                $query->where('`'.bqSQL($type).'` = 1');
            }

            if ($orderBySize) {
                $query->orderBy('`width` DESC, `height` DESC, `name` ASC');
            } else {
                $query->orderBy('`name` ASC');
            }

            $cache[$type] = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($query);
        }

        return $cache[$type];
    }

    /**
     * Check if type already is already registered in database
     *
     * @param string $typeName Name
     *
     * @return int Number of results found
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function typeAlreadyExists($typeName)
    {
        if (!Validate::isImageTypeName($typeName)) {
            die(Tools::displayError());
        }

        Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('`id_image_type`')
                ->from('image_type')
                ->where('`name` = \''.pSQL($typeName).'\'')
        );

        return Db::getInstance()->NumRows();
    }

    /**
     * Find an existing variant of a specific image type. This is for
     * retrocompatibility, installation of properly named image types was
     * broken for a long time, from before 1.0.0, up to 1.1.0.
     *
     * @param string $name
     *
     * @return string
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @version 1.0.0 Initial version
     */
    public static function getFormatedName($name)
    {
        $themeName = Context::getContext()->shop->theme_name;
        $nameWithoutTheme = str_replace(
            ['_'.$themeName, $themeName.'_'],
            '',
            $name
        );

        //check if the theme name is already in $name if yes only return $name
        if ($themeName
            && strpos($name, $themeName) !== false
            && static::typeAlreadyExists($name)) {
            return $name;
        } elseif (static::typeAlreadyExists($nameWithoutTheme.'_'.$themeName)) {
            return $nameWithoutTheme.'_'.$themeName;
        } elseif (static::typeAlreadyExists($themeName.'_'.$nameWithoutTheme)) {
            return $themeName.'_'.$nameWithoutTheme;
        } else {
            return $nameWithoutTheme.'_default';
        }
    }

    /**
     * Finds image type definition by name and type
     *
     * @param string $name
     * @param string $type
     * @param int    $order Deprecated.
     *
     * @return bool|mixed
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @version 1.0.0 Initial version.
     * @version 1.1.0 Reworked entirely, $order deprecated.
     */
    public static function getByNameNType($name, $type = '', $order = null)
    {
        static $cache = null;

        if (isset($order)) {
            Tools::displayParameterAsDeprecated('order');
        }

        if ( ! $cache) {
            $results = static::getImagesTypes();
            $resultTypes = [
                'products',
                'categories',
                'manufacturers',
                'suppliers',
                'scenes',
                'stores',
            ];

            foreach ($results as $result) {
                foreach ($resultTypes as $resultType) {
                    $key = $result['name'].'_'.$resultType;
                    $cache[$key] = $result;
                }
            }
        }

        $return = false;
        if (isset($cache[$name.'_'.$type])) {
            $return = $cache[$name.'_'.$type];
        }

        return $return;
    }
}
