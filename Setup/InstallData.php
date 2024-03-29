<?php

namespace MagentoEse\LumaDEAttributes\Setup;

use Magento\Catalog\Model\Product\Attribute\Repository;
use Magento\Eav\Model\Entity\Attribute;
use Magento\Framework\File\Csv;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\SampleData\FixtureManager;
use Magento\Store\Model\Store;
use Symfony\Component\Config\Definition\Exception\Exception;


/**
 * @codeCoverageIgnore
 */
class InstallData implements InstallDataInterface
{

   /**
    * 
    * @var \Magento\Framework\Setup\SampleData\Context
    */
    protected $sampleDataContext;

    /**
     * 
     * @var Store
     */
    protected $storeView;

    /**
     * 
     * @var Attribute
     */
    protected $attribute;

    /**
     * 
     * @var Repository
     */
    protected $productAttributeRepository;

    /**
     * 
     * @var array
     */
    protected $config;

    /**
     * 
     * @var FixtureManager
     */
    protected $fixtureManager;

    /**
     * 
     * @var Csv
     */
    protected $csvReader;

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
                try {
                    $attributeOptions = $this->productAttributeRepository->get($_attribcode)->getOptions();
                }catch(\Magento\Framework\Exception\NoSuchEntityException $e){
                    //Ignore if attribute is not found
                    continue;
                }
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

