<?php
/**
 * 2007-2016 PrestaShop
 *
 * Thirty Bees is an extension to the PrestaShop e-commerce software developed by PrestaShop SA
 * Copyright (C) 2017 Thirty Bees
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
 *  @author    Thirty Bees <contact@thirtybees.com>
 *  @author    PrestaShop SA <contact@prestashop.com>
 *  @copyright 2017 Thirty Bees
 *  @copyright 2007-2016 PrestaShop SA
 *  @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  PrestaShop is an internationally registered trademark & property of PrestaShop SA
 */

class TranslatedConfigurationCore extends Configuration
{
    protected $webserviceParameters = [
        'objectNodeName' => 'translated_configuration',
        'objectsNodeName' => 'translated_configurations',
        'fields' => [
            'value' => [],
            'date_add' => [],
            'date_upd' => [],
        ],
    ];

    public static $definition = [
        'table' => 'configuration',
        'primary' => 'id_configuration',
        'multilang' => true,
        'fields' => [
            'name' =>            ['type' => self::TYPE_STRING, 'validate' => 'isConfigName', 'required' => true, 'size' => 32],
            'id_shop_group' =>    ['type' => self::TYPE_NOTHING, 'validate' => 'isUnsignedId'],
            'id_shop' =>        ['type' => self::TYPE_NOTHING, 'validate' => 'isUnsignedId'],
            'value' =>            ['type' => self::TYPE_STRING, 'lang' => true],
            'date_add' =>        ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
            'date_upd' =>        ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
        ],
    ];

    public function __construct($id = null, $id_lang = null)
    {
        $this->def = ObjectModel::getDefinition($this);
        // Check if the id configuration is set in the configuration_lang table.
        // Otherwise configuration is not set as translated configuration.
        if ($id !== null) {
            $id_translated = Db::getInstance()->executeS('				SELECT `'.bqSQL($this->def['primary']).'`
				FROM `'.bqSQL(_DB_PREFIX_.$this->def['table']).'_lang`
				WHERE `'.bqSQL($this->def['primary']).'`='.(int)$id.' LIMIT 0,1
			');

            if (empty($id_translated)) {
                $id = null;
            }
        }
        parent::__construct($id, $id_lang);
    }

    public function add($autodate = true, $nullValues = false)
    {
        return $this->update($nullValues);
    }

    public function update($nullValues = false)
    {
        $ishtml = false;
        foreach ($this->value as $i18n_value) {
            if (Validate::isCleanHtml($i18n_value)) {
                $ishtml = true;
                break;
            }
        }
        Configuration::updateValue($this->name, $this->value, $ishtml);

        $last_insert = Db::getInstance()->getRow('
			SELECT `id_configuration` AS id
			FROM `'._DB_PREFIX_.'configuration`
			WHERE `name` = \''.pSQL($this->name).'\'');
        if ($last_insert) {
            $this->id = $last_insert['id'];
        }

        return true;
    }

    public function getWebserviceObjectList($sql_join, $sql_filter, $sql_sort, $sql_limit)
    {
        $query = '
		SELECT DISTINCT main.`'.$this->def['primary'].'` FROM `'._DB_PREFIX_.$this->def['table'].'` main
		'.$sql_join.'
		WHERE id_configuration IN
		(	SELECT id_configuration
			FROM '._DB_PREFIX_.$this->def['table'].'_lang
		) '.$sql_filter.'
		'.($sql_sort != '' ? $sql_sort : '').'
		'.($sql_limit != '' ? $sql_limit : '').'
		';
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($query);
    }
}
