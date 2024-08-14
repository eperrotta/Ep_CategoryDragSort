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

namespace Ep\CategoryDragSort\Plugin\Catalog\Category;

use Closure;
use Ep\CategoryDragSort\Model\ResourceModel\Category\Product\Position;
use Exception;
use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Catalog\Model\ResourceModel\Category;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Store\Model\Store;

class SaveProductsPositions
{
    private readonly Position $saveHandler;
    private readonly SerializerInterface $serializer;

    public function __construct(
        Position $saveHandler,
        SerializerInterface $serializer
    ) {
        $this->serializer = $serializer;
        $this->saveHandler = $saveHandler;
    }

    public function aroundSave(
        Category $categoryResource,
        Closure $proceed,
        AbstractModel $category
    ) {
        if ($category->getId() && $category->getData('sorted_products')) {
            $this->unserializeProductPositions($category);

            $categoryResource->addCommitCallback(
                function () use ($category) {
                    $affectedProductIds = $this->getAffectedProductIds($category);
                    $category->setAffectedProductIds($affectedProductIds);
                    $this->saveHandler->saveProductPositions($category);
                }
            );
        }

        return $proceed($category);
    }

    private function getAffectedProductIds(CategoryInterface $category): array
    {
        $oldPositionProductIds = array_keys($this->saveHandler->getProductPositionsByCategory($category));
        $defaultPositionProductIds = [];
        $newPositionProductIds = array_keys($category->getData('sorted_products'));

        if (true === (bool)$category->getUseStorePositions()) {
            $defaultPositionProductIds = array_keys(
                $this->saveHandler->getProductPositions(
                    $category->getId(),
                    Store::DEFAULT_STORE_ID
                )
            );
        }

        $affectedProductIds = array_merge(
            $oldPositionProductIds,
            $defaultPositionProductIds,
            $newPositionProductIds
        );

        if ($category->getAffectedProductIds()) {
            $affectedProductIds = array_merge($affectedProductIds, $category->getAffectedProductIds());
        }

        return array_unique($affectedProductIds);
    }

    private function unserializeProductPositions(CategoryInterface $category): void
    {
        $productPositions = $category->getData('sorted_products') ? $category->getData('sorted_products') : [];

        if (is_string($productPositions)) {
            try {
                $productPositions = $this->serializer->unserialize($productPositions);
            } catch (Exception) {
                $productPositions = [];
            }
        }

        $category->setData('sorted_products', $productPositions);
    }
}
