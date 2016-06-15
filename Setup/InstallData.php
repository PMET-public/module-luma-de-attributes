<?php

namespace MagentoEse\LumaDEAttributes\Setup;

use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;


    /**
 * @codeCoverageIgnore
 */
class InstallData implements InstallDataInterface
{

    protected $sampleDataContext;
    protected $storeView;
    protected $attribute;
    protected $productAttributeRepository;


    public function __construct(\Magento\Framework\Setup\SampleData\Context $sampleDataContext,
                                \Magento\Store\Model\Store $storeView,
                                \Magento\Eav\Model\Entity\Attribute $attribute,
                                \Magento\Catalog\Model\Product\Attribute\Repository $productAttributeRepository)
    {

        $this->config = require 'Config.php';
        $this->fixtureManager = $sampleDataContext->getFixtureManager();
        $this->csvReader = $sampleDataContext->getCsvReader();
        $this->storeView = $storeView;
        $this->attribute = $attribute;
        $this->productAttributeRepository = $productAttributeRepository;
    }

    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        //get view id from view code
        $_viewId = $this->storeView->load($this->config['viewCode'])->getStoreId();

        //get attribute labels and values translation
        $_fileName = $this->fixtureManager->getFixture('MagentoEse_LumaDEAttributes::fixtures/AttributeLabels.csv');
        $_attributeLabels = $this->csvReader->getData($_fileName);
        $_fileName = $this->fixtureManager->getFixture('MagentoEse_LumaDEAttributes::fixtures/AttributeValues.csv');
        $_attributeValues= $this->csvReader->getData($_fileName);

        //loop though attributes to translate
        foreach ($this->config['attributesToTranslate'] as $_attribcode) {
            //get default label for that code
            $_defaultLabel = $this->attribute->loadByCode(\Magento\Catalog\Model\Product::ENTITY, $_attribcode)->getStoreLabel(0);
            //find translation
            foreach ($_attributeLabels as $_labelToTranslate) {
                if ($_labelToTranslate[0] == $_defaultLabel) {
                    //save label - default value also needs to be set as part of the array
                    $attribute = $this->attribute->loadByCode(\Magento\Catalog\Model\Product::ENTITY, $_attribcode);
                    $attribute->setData('frontend_label', [0 => $_defaultLabel, $_viewId => $_labelToTranslate[1]])->save();
                    continue;
                }
            }
            //set values for attribute

            $attributeOptions = $this->productAttributeRepository->get($_attribcode)->getOptions();
            foreach ($attributeOptions as $attributeOption) {
                $_optionValue = $attributeOption->getValue();
                $_optionLabel = $attributeOption->getLabel();
                foreach ($_attributeValues as $_attributeValue) {
                    if ($_optionLabel == $_attributeValue[0]) {
                        $attribute->setData('option', array('value' => array($_optionValue => [0 => $_optionLabel, $_viewId => $_attributeValue[1]])))->save();
                        continue;
                    }
                }
            }

        }
    }
}

