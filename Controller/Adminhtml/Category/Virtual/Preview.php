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

namespace Ep\CategoryDragSort\Controller\Adminhtml\Category\Virtual;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Catalog\Model\CategoryFactory;
use Ep\CategoryDragSort\Model\PreviewFactory;
use Ep\CategoryDragSort\Model\Preview as PreviewModel;
use Magento\Framework\Serialize\SerializerInterface;

class Preview extends Action
{
    private CategoryFactory $categoryFactory;
    private PreviewFactory $previewModelFactory;
    private SerializerInterface $serializer;

    /**
     * @param Context $context
     * @param CategoryFactory $categoryFactory
     * @param PreviewFactory $previewModelFactory
     * @param SerializerInterface $serializer
     */
    public function __construct(
        Context $context,
        CategoryFactory $categoryFactory,
        PreviewFactory $previewModelFactory,
        SerializerInterface $serializer
    ) {
        parent::__construct($context);
        $this->categoryFactory = $categoryFactory;
        $this->previewModelFactory = $previewModelFactory;
        $this->serializer = $serializer;
    }

    /**
     * {@inheritDoc}
     */
    public function execute()
    {
        $responseData = $this->getPreviewObject()->getData();
        $json = $this->serializer->serialize($responseData);
        return $this->getResponse()->representJson($json);
    }

    /**
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     * {@inheritDoc}
     */
    protected function _isAllowed(): bool
    {
        return $this->_authorization->isAllowed('Magento_Catalog::categories');
    }

    /**
     * Load and initialize the preview model.
     *
     */
    private function getPreviewObject(): PreviewModel
    {
        $category = $this->getCategory();
        $pageSize = $this->getPageSize();
        $search = $this->getRequest()->getParam('search');

        return $this->previewModelFactory->create(['category' => $category, 'size' => $pageSize, 'search' => $search]);
    }

    private function getCategory(): CategoryInterface
    {
        $category = $this->loadCategory();

        $this->addSelectedProducts($category)
            ->setSortedProducts($category);

        return $category;
    }

    private function loadCategory(): CategoryInterface
    {
        $category = $this->categoryFactory->create();
        $storeId = $this->getRequest()->getParam('store');
        $categoryId = $this->getRequest()->getParam('entity_id');

        $category->setStoreId($storeId)->load($categoryId);

        return $category;
    }

    private function addSelectedProducts(CategoryInterface $category): self
    {
        $selectedProducts = $this->getRequest()->getParam('selected_products', []);

        $addedProducts = isset($selectedProducts['added_products']) ? $selectedProducts['added_products'] : [];
        $category->setAddedProductIds($addedProducts);

        $deletedProducts = isset($selectedProducts['deleted_products']) ? $selectedProducts['deleted_products'] : [];
        $category->setDeletedProductIds($deletedProducts);

        return $this;
    }

    private function setSortedProducts(CategoryInterface $category): self
    {
        $productPositions = $this->getRequest()->getParam('product_position', []);
        $category->setSortedProductIds(array_keys($productPositions));

        return $this;
    }

    private function getPageSize(): int
    {
        return (int)$this->getRequest()->getParam('page_size');
    }
}
