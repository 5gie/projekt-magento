<?php

namespace Custom\LatestProducts\Block;

use Zend_Db_Expr;


class LatestProducts extends AbstractLatestProducts
{
    public function getProductCollection()
    {
        $visibleProducts = $this->_catalogProductVisibility->getVisibleInCatalogIds();
        $collection = $this->_productCollectionFactory->create()->setVisibility($visibleProducts);
        $collection->addAttributeToSelect('*');

        // $collection->load();
        // $products = $collection->getItems();

        $collection      = $this->_addProductAttributesAndPrices($collection)
        //     ->addAttributeToFilter(
        //         'news_from_date',
        //         ['date' => true, 'to' => $this->getEndOfDayDate()],
        //         'left'
        //     )
        //     ->addAttributeToFilter(
        //         'news_to_date',
        //         [
        //             'or' => [
        //                 0 => ['date' => true, 'from' => $this->getStartOfDayDate()],
        //                 1 => ['is' => new Zend_Db_Expr('null')],
        //             ]
        //         ],
        //         'left'
        //     )
            // ->addAttributeToSort(
            //     'news_from_date',
            //     'desc'
            // )
            ->addAttributeToSort(
                'created_at',
                'desc'
            )
            ->addStoreFilter($this->getStoreId())
            ->setPageSize($this->getProductsCount());
        //     ->setPageSize(8);

        return $collection;
    }

}