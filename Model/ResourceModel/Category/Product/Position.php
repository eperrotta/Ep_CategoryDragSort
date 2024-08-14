<?php
/**
 * DISCLAIMER
 *
 * This is a derivative work from Smile's ElasticsuiteVirtualCategory module.
 * Original credits: Aurelien FOUCRET <aurelien.foucret@smile.fr> 2020 Smile
 *
 * @category  Ep
 * @package   CategoryDragSort
 * @author    Enzo PERROTTA <enzo.perrotta@gmail.com>
 * @copyright 2024 Enzo PERROTTA
 * @license   Open Software License ("OSL") v. 3.0
 */

declare(strict_types=1);

namespace Ep\CategoryDragSort\Model\ResourceModel\Category\Product;

use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Position extends AbstractDb
{
    /**
     * @var string
     */
    private const TABLE_NAME = 'catalog_category_product';

    public function getProductPositions(int $categoryId): array
    {
        try {
            $select = $this->getBaseSelect()
                ->where('category_id = ?', $categoryId)
                ->where('position IS NOT NULL')
                ->columns(['product_id', 'position']);
            return $this->getConnection()->fetchPairs($select);
        } catch (\Zend_Db_Select_Exception) {
        }
        return [];
    }

    public function getProductPositionsByCategory(CategoryInterface|int $category): array
    {
        $storeId = \Magento\Store\Model\Store::DEFAULT_STORE_ID;
        if (is_object($category)) {
            if ($category->getUseStorePositions()) {
                $storeId = $category->getStoreId();
            }
            $category = (int)$category->getId();
        }

        return $this->getProductPositions($category, $storeId);
    }

    public function saveProductPositions(CategoryInterface $category): self
    {
        // Can be 0 if not on a store view.
        $storeId = $category->getStoreId();

        // If on a store view, and no store override of positions, clean up existing store records.
        if ($storeId && !$category->getUseStorePositions()) {
            $category->setSortedProducts([]);
        }

        $newProductPositions = $category->getSortedProducts();

        $deleteConditions = [
            $this->getConnection()->quoteInto('category_id = ?', (int)$category->getId())
        ];

        if (!empty($newProductPositions) || !empty($blacklistedProducts)) {
            $insertData = [];
            $updatedProductIds = array_keys($newProductPositions);

            foreach ($updatedProductIds as $productId) {
                $insertData[] = [
                    'category_id' => $category->getId(),
                    'product_id' => $productId,
                    'position' => $newProductPositions[$productId] ?? null,
                ];
            }

            $deleteConditions[] = $this->getConnection()->quoteInto('product_id NOT IN (?)', $updatedProductIds);
            $this->getConnection()->insertOnDuplicate(
                $this->getMainTable(),
                $insertData,
                array_keys(current($insertData))
            );
        }

        $this->getConnection()->delete($this->getMainTable(), implode(' AND ', $deleteConditions));

        return $this;
    }

    /**
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     * {@inheritDoc}
     */
    protected function _construct()
    {
        $this->_setMainTable(self::TABLE_NAME);
    }

    private function getBaseSelect(): \Zend_Db_Select
    {
        $select = $this->getConnection()->select();
        $select->from(['main_table' => $this->getMainTable()], []);
        return $select;
    }
}
