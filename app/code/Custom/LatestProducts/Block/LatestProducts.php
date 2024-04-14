<?php

namespace Custom\LatestProducts\Block;

use Magento\Framework\View\Element\Template;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;

class LatestProducts extends Template
{
    protected $productCollectionFactory;

    public function __construct(
        Template\Context $context,
        CollectionFactory $productCollectionFactory,
        array $data = []
    ) {
        $this->productCollectionFactory = $productCollectionFactory;
        parent::__construct($context, $data);
    }

    public function getProductCollection()
    {
        return $this->productCollectionFactory->create()
            ->addAttributeToSelect('*')
            ->setPageSize(5)
            ->setOrder('created_at', 'desc');
    }
}
