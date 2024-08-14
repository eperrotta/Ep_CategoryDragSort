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
use Magento\Customer\Api\Data\GroupInterface;
use Magento\Catalog\Helper\Image as ImageHelper;

class ItemDataFactory
{
    /**
     * @var ImageHelper
     */
    private $imageHelper;

    /**
     * Constructor.
     *
     * @param ImageHelper $imageHelper Image helper.
     */
    public function __construct(ImageHelper $imageHelper)
    {
        $this->imageHelper = $imageHelper;
    }

    /**
     * Item data.
     *
     * @param ProductInterface $product Product.
     *
     * @return array
     */
    public function getData(ProductInterface $product)
    {
        $productItemData = [
            'id' => $product->getId(),
            'sku' => $product->getSku(),
            'name' => $product->getName(),
            'price' => $product->getFinalPrice(),
            'image' => $this->getImageUrl($product),
            'is_in_stock' => $product->isInStock(),
        ];

        return $productItemData;
    }

    /**
     * Get resized image URL.
     *
     * @param ProductInterface $product Product.
     *
     * @return string
     */
    private function getImageUrl(ProductInterface $product)
    {
        $this->imageHelper->init($product, 'product_sorter_image');

        return $this->imageHelper->getUrl();
    }

    /**
     * Return the ES source document for the current product.
     *
     * @param ProductInterface $product Product.
     *
     * @return array
     */
    private function getDocumentSource(ProductInterface $product)
    {
        return $product->getDocumentSource() ?: [];
    }
}
