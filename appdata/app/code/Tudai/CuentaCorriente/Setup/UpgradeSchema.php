<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Tudai\CuentaCorriente\Setup;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Customer\Setup\CustomerSetupFactory;
use Magento\Customer\Model\Customer;
use Magento\Eav\Model\Entity\Attribute\Set as AttributeSet;
use Magento\Eav\Model\Entity\Attribute\SetFactory as AttributeSetFactory;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
/**
 * Upgrade Data script
 * @codeCoverageIgnore
 */
class UpgradeData implements UpgradeDataInterface
{
  /**
   * @var CustomerSetupFactory
   */
  protected $customerSetupFactory;
  /**
   * @var AttributeSetFactory
   */
  private $attributeSetFactory;
  /**
   * Init
   *
   * @param CustomerSetupFactory $customerSetupFactory
   * @param AttributeSetFactory $attributeSetFactory
   */
   public function __construct(
         CustomerSetupFactory $customerSetupFactory,
         AttributeSetFactory $attributeSetFactory
     ) {
         $this->customerSetupFactory = $customerSetupFactory;
         $this->attributeSetFactory = $attributeSetFactory;
     }
    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();
        if ($context->getVersion()
            && version_compare($context->getVersion(), '1.1.0') < 0
        ) {
          /**
             * @var CustomerSetup $customerSetup
             */
            $customerSetup = $this->customerSetupFactory->create(['setup' => $setup]);
            $customerEntity = $customerSetup->getEavConfig()->getEntityType(Customer::ENTITY);
            $attributeSetId = $customerEntity->getDefaultAttributeSetId();
            /**
             * @var $attributeSet AttributeSet
             */
            $attributeSet = $this->attributeSetFactory->create();
            $attributeGroupId = $attributeSet->getDefaultGroupId($attributeSetId);
            $customerSetup->addAttribute(Customer::ENTITY, 'enable_customer_credit', [
              'type'         => 'int',
              'label'        => 'Enable Customer Credit',
              'input'        => 'boolean',
              'required'     => false,
              'visible'      => true,
              'user_defined' => true,
              'position'     => 210,
              'system'       => 0
            ]);
            $attribute = $customerSetup->getEavConfig()->getAttribute(Customer::ENTITY, 'enable_customer_credit')
                ->addData([
                    'attribute_set_id'   => $attributeSetId,
                    'attribute_group_id' => $attributeGroupId,
                    'used_in_forms'      => ['adminhtml_customer'],//you can use other forms also ['adminhtml_customer_address', 'customer_address_edit', 'customer_register_address']
                ]);
            $attribute->save();
        }
    }
    /**
     * @param \Magento\Catalog\Setup\CategorySetup $categorySetup
     * @return void
     */
    private function changePriceAttributeDefaultScope($categorySetup)
    {
        $entityTypeId = $categorySetup->getEntityTypeId(\Magento\Catalog\Model\Product::ENTITY);
        foreach (['price', 'cost', 'special_price'] as $attributeCode) {
            $attribute = $categorySetup->getAttribute($entityTypeId, $attributeCode);
            $categorySetup->updateAttribute(
                $entityTypeId,
                $attribute['attribute_id'],
                'is_global',
                \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL
            );
        }
    }
}
