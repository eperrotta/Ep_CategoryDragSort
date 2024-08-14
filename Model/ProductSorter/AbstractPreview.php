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

namespace Ep\CategoryDragSort\Model\ProductSorter;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\ResourceModel\Product;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;

abstract class AbstractPreview
{
    private readonly ProductCollectionFactory $collectionFactory;
    private readonly ItemDataFactory $itemFactory;
    private readonly int $storeId;
    private readonly int $size;
    private readonly string $searchRequestName;

    public function __construct(
        ProductCollectionFactory $collectionFactory,
        ItemDataFactory $itemFactory,
        int $storeId,
        int $size = 10,
        string $searchRequestName = 'catalog_view_container'
    ) {
        $this->size = $size;
        $this->storeId = $storeId;
        $this->itemFactory = $itemFactory;
        $this->searchRequestName = $searchRequestName;
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * {@inheritDoc}
     */
    public function getData(): array
    {
        $data = $this->getUnsortedProductData();

        $sortedProducts = $this->getSortedProducts();
        $data['products'] = $this->preparePreviewItems(array_merge($sortedProducts, $data['products']));

        return $data;
    }

    /**
     * Apply custom logic to product collection.
     *
     * @param Collection $collection Product collection.
     *
     * @return Collection
     */
    protected function prepareProductCollection(Collection $collection): Collection
    {
        return $collection;
    }

    /**
     * List of sorted product ids.
     *
     * @return array
     */
    abstract protected function getSortedProductIds(): array;

    /**
     * Convert an array of products to an array of preview items.
     *
     * @param Product[] $products Product list.
     *
     * @return array
     */
    protected function preparePreviewItems($products = []): array
    {
        $items = [];

        foreach ($products as $product) {
            $items[$product->getId()] = $this->itemFactory->getData($product);
        }

        return array_values($items);
    }

    protected function getProductCollection(): Collection
    {
        $productCollection = $this->collectionFactory->create(['searchRequestName' => $this->searchRequestName]);
        $productCollection->setStoreId($this->storeId)
            ->addAttributeToSelect(['name', 'small_image']);

        return $this->prepareProductCollection($productCollection);
    }

    /**
     * Return a collection with all products manually sorted loaded.
     *
     * @return ProductInterface[]
     */
    protected function getSortedProducts(): array
    {
        $products = [];
        $productIds = $this->getSortedProductIds();

        if ($productIds && count($productIds)) {
            $pageSize = count($productIds) > $this->size ? $this->size : count($productIds);
            $productCollection = $this->getProductCollection()->setPageSize($pageSize);
            $productCollection->addAttributeToFilter('entity_id', ['in' => $productIds]);

            $products = $productCollection->getItems();
        }

        $sortedProducts = [];

        foreach ($this->getSortedProductIds() as $productId) {
            if (isset($products[$productId])) {
                $sortedProducts[$productId] = $products[$productId];
            }
        }

        return $sortedProducts;
    }

    /**
     * Return a collection with products that match the current preview.
     *
     * @return array
     */
    private function getUnsortedProductData(): array
    {
        $productCollection = $this->getProductCollection()->setPageSize($this->size);
        $items = $productCollection->getItems();
        $size = $productCollection->getSize();
        return ['products' => $items, 'size' => $size];
    }
}
