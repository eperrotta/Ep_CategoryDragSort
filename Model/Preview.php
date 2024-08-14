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

namespace Ep\CategoryDragSort\Model;

use Magento\Catalog\Model\Config;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Framework\App\RequestInterface;
use Ep\CategoryDragSort\Model\ProductSorter\AbstractPreview;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Ep\CategoryDragSort\Model\ProductSorter\ItemDataFactory;
use Magento\Framework\Data\Collection as BaseCollection;

class Preview extends AbstractPreview
{
    /**
     * Default customer group id.
     */
    private const DEFAULT_CUSTOMER_GROUP_ID = '0';

    /**
     * @var CategoryInterface
     */
    private $category;

    /**
     * @var \Magento\Framework\App\RequestInterface|mixed
     */
    private $request;

    /**
     * @var \Magento\Catalog\Model\Config|mixed
     */
    private $categoryConfig;

    /**
     * @var string
     */
    private $sortBy;


    /**
     * @param CategoryInterface $category
     * @param ProductCollectionFactory $productCollectionFactory
     * @param ItemDataFactory $previewItemFactory
     * @param int $size
     * @param string $search
     * @param RequestInterface|null $request
     * @param Config|null $categoryConfig
     */
    public function __construct(
        CategoryInterface $category,
        ProductCollectionFactory $productCollectionFactory,
        ItemDataFactory $previewItemFactory,
        int $size = 10,
        string $search = '',
        RequestInterface $request = null,
        Config $categoryConfig = null
    ) {
        parent::__construct($productCollectionFactory, $previewItemFactory, $category->getStoreId(), $size, $search);
        $this->category = $category;
        $this->request = $request ?: \Magento\Framework\App\ObjectManager::getInstance()->get(RequestInterface::class);
        $this->categoryConfig = $categoryConfig ?: \Magento\Framework\App\ObjectManager::getInstance()->get(
            Config::class
        );
    }

    /**
     * {@inheritDoc}
     */
    protected function prepareProductCollection(Collection $collection): Collection
    {
        $collection->setVisibility([Visibility::VISIBILITY_IN_CATALOG, Visibility::VISIBILITY_BOTH]);
        $collection->addCategoryFilter($this->category);

        $sortBy = $this->getSortBy() ?? 'position';
        $directionFallback = $sortBy !== 'position' ? BaseCollection::SORT_ORDER_ASC : BaseCollection::SORT_ORDER_DESC;

        $collection->setOrder($sortBy, $this->request->getParam('sort_direction', $directionFallback));
        $collection->addPriceData(self::DEFAULT_CUSTOMER_GROUP_ID, $this->category->getStoreId());

        return $collection;
    }

    /**
     * Return the list of sorted product ids.
     *
     * @return array
     */
    protected function getSortedProductIds(): array
    {
        return ($this->getSortBy() === 'position') ? $this->category->getSortedProductIds() : [];
    }

    /**
     * {@inheritDoc}
     */
    protected function preparePreviewItems($products = []): array
    {
        $items = parent::preparePreviewItems($products);

        if ($this->getSortBy() !== 'position') {
            // In order to sort the product in admin category grid, we need to set the position value
            // if the sort order is different from position because the products are sorted in js.
            // We also disable manual sorting when sort order is not position.
            array_walk($items, function (&$productData, $index) {
                $productData['position'] = $index;
                $productData['can_use_manual_sort'] = false;
            });
        }

        return $items;
    }

    /**
     * Get sort by attribute.
     *
     * @return string
     */
    private function getSortBy(): string
    {
        if (!$this->sortBy) {
            $useConfig = $this->request->getParam('use_config', []);
            $useConfig = array_key_exists('default_sort_by', $useConfig) && $useConfig['default_sort_by'] == 'true';
            $defaultSortBy = $this->categoryConfig->getProductListDefaultSortBy();
            $this->sortBy = $useConfig ? $defaultSortBy : $this->request->getParam('default_sort_by');
        }

        return $this->sortBy;
    }
}
