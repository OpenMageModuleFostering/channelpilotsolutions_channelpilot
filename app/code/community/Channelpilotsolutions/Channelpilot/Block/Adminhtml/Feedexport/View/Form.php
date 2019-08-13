<?php

/**
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the GNU General Public License (GPL 3)
 * that is bundled with this package in the file LICENSE.txt
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Channelpilotsolutions_Channelpilot to newer
 * versions in the future. If you wish to customize Channelpilotsolutions_Channelpilot for your
 * needs please refer to http://www.channelpilot.com for more information.
 *
 * @category        Channelpilotsolutions
 * @package         Channelpilotsolutions_Channelpilot
 * @copyright       Copyright (c) 2012 <info@channelpilot.com> - www.channelpilot.com
 * @author          Björn Wehner <info@channelpilot.com>
 * @license         <http://www.gnu.org/licenses/> GNU General Public License (GPL 3)
 * @link            http://www.channelpilot.com
 */
class Channelpilotsolutions_Channelpilot_Block_Adminhtml_Feedexport_View_Form extends Mage_Adminhtml_Block_Widget_Form
{
    protected function _prepareForm()
    {
        $form = new Varien_Data_Form(array(
            'id'        => 'view_form',
            'action'    => '',
            'method'    => 'post',
        ));
        $form->setUseContainer(true);
        $this->setForm($form);

        $data = Mage::registry('cp_product_feed_data');

        if(is_array($data) && isset($data['product_data'])) {
            $productData = $data['product_data'];

            $fieldset = $form->addFieldset('view_product_data', array(
            ));

            $xml = simplexml_load_string($productData);
            $children = $xml->children();
            $data = array();
            foreach($children as $key => $value) {
                $data[$key] = $value;
                $fieldType = (strlen($value) > 50) ? 'textarea' : 'text';
                $fieldset->addField($key, $fieldType, array(
                    'label'     => $key,
                    'name'      => $key,
                    'disabled'  => true,
                ));
            }

            $form->setValues($data);
        }

        return parent::_prepareForm();
    }
}