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

/**
 * @property PrestaShopLogger $object
 */
class AdminLogsControllerCore extends AdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'log';
        $this->className = 'PrestaShopLogger';
        $this->lang = false;
        $this->noLink = true;

        $this->fields_list = [
            'id_log' => [
                'title' => $this->l('ID'),
                'align' => 'text-center',
                'class' => 'fixed-width-xs'
            ],
            'employee' => [
                'title' => $this->l('Employee'),
                'havingFilter' => true,
                'callback' => 'displayEmployee',
                'callback_object' => $this
            ],
            'severity' => [
                'title' => $this->l('Severity (1-4)'),
                'align' => 'text-center',
                'class' => 'fixed-width-xs'
            ],
            'message' => [
                'title' => $this->l('Message')
            ],
            'object_type' => [
                'title' => $this->l('Object type'),
                'class' => 'fixed-width-sm'
            ],
            'object_id' => [
                'title' => $this->l('Object ID'),
                'align' => 'center',
                'class' => 'fixed-width-xs'
            ],
            'error_code' => [
                'title' => $this->l('Error code'),
                'align' => 'center',
                'prefix' => '0x',
                'class' => 'fixed-width-xs'
            ],
            'date_add' => [
                'title' => $this->l('Date'),
                'align' => 'right',
                'type' => 'datetime'
            ]
        ];

        $this->fields_options = [
            'general' => [
                'title' =>    $this->l('Logs by email'),
                'icon' => 'icon-envelope',
                'fields' =>    [
                    'PS_LOGS_BY_EMAIL' => [
                        'title' => $this->l('Minimum severity level'),
                        'hint' => $this->l('Enter "5" if you do not want to receive any emails.').'<br />'.$this->l('Emails will be sent to the shop owner.'),
                        'cast' => 'intval',
                        'type' => 'text'
                    ]
                ],
                'submit' => ['title' => $this->l('Save')]
            ]
        ];
        $this->list_no_link = true;
        $this->_select .= 'CONCAT(LEFT(e.firstname, 1), \'. \', e.lastname) employee';
        $this->_join .= ' LEFT JOIN '._DB_PREFIX_.'employee e ON (a.id_employee = e.id_employee)';
        $this->_use_found_rows = false;
        parent::__construct();
    }

    public function processDelete()
    {
        if (PrestaShopLogger::eraseAllLogs()) {
            Tools::redirectAdmin(Context::getContext()->link->getAdminLink('AdminLogs'));
        }
    }

    public function initToolbar()
    {
        parent::initToolbar();
        $this->toolbar_btn['delete'] = [
            'short' => 'Erase',
            'desc' => $this->l('Erase all'),
            'js' => 'if (confirm(\''.$this->l('Are you sure?').'\')) document.location = \''.Tools::safeOutput($this->context->link->getAdminLink('AdminLogs')).'&amp;token='.$this->token.'&amp;deletelog=1\';'
        ];
        unset($this->toolbar_btn['new']);
    }

    public function displayEmployee($value, $tr)
    {
        $template = $this->context->smarty->createTemplate('controllers/logs/employee_field.tpl', $this->context->smarty);
        $employee = new Employee((int)$tr['id_employee']);
        $template->assign(
            [
            'employee_image' => $employee->getImage(),
            'employee_name' => $value
            ]
        );
        return $template->fetch();
    }
}
