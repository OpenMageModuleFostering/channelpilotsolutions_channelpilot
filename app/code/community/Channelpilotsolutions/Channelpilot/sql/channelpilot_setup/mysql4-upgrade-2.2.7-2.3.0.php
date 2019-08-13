<?php

$installer = $this;
$installer->startSetup();
$setup = new Mage_Eav_Model_Entity_Setup('core_setup');

$setup->addAttribute('order', 'expectedShippingTime', array(
   'position'      => 1,
    'input'         => 'text',
    'type'          => 'varchar',
    'label'         => 'Choose delivery date',
    'visible'       => 1,
    'required'      => 0,
    'user_defined' => 1,
    'global'        => 1,
    'visible_on_front'  => 1,
));

$installer->endSetup();
?>