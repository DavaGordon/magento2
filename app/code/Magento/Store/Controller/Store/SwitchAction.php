<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Store\Controller\Store;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context as ActionContext;
use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\StoreCookieManagerInterface;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\Store\Model\StoreIsInactiveException;
use Magento\Store\Model\StoreResolver;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\StoreSwitcher;

/**
 * Switch current store view.
 */
class SwitchAction extends Action
{
    /**
     * @var StoreCookieManagerInterface
     */
    protected $storeCookieManager;

    /**
     * @var HttpContext
     */
    protected $httpContext;

    /**
     * @var StoreRepositoryInterface
     */
    protected $storeRepository;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var StoreSwitcher
     */
    private $storeSwitcher;

    /**
     * Initialize dependencies.
     *
     * @param ActionContext $context
     * @param StoreCookieManagerInterface $storeCookieManager
     * @param HttpContext $httpContext
     * @param StoreRepositoryInterface $storeRepository
     * @param StoreManagerInterface $storeManager
     * @param StoreSwitcher $storeSwitcher
     */
    public function __construct(
        ActionContext $context,
        StoreCookieManagerInterface $storeCookieManager,
        HttpContext $httpContext,
        StoreRepositoryInterface $storeRepository,
        StoreManagerInterface $storeManager,
        StoreSwitcher $storeSwitcher = null
    ) {
        parent::__construct($context);
        $this->storeCookieManager = $storeCookieManager;
        $this->httpContext = $httpContext;
        $this->storeRepository = $storeRepository;
        $this->messageManager = $context->getMessageManager();
        $this->storeSwitcher = $storeSwitcher ?: ObjectManager::getInstance()->get(StoreSwitcher::class);
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|void
     */
    public function execute()
    {
        $targetStoreCode = $this->_request->getParam(
            StoreResolver::PARAM_NAME,
            $this->storeCookieManager->getStoreCodeFromCookie()
        );
        $fromStoreCode = $this->_request->getParam('___from_store');

        $requestedUrlToRedirect = $this->_redirect->getRedirectUrl();
        $redirectUrl = $requestedUrlToRedirect;

        $error = null;
        try {
            $fromStore = $this->storeRepository->get($fromStoreCode);
            $targetStore = $this->storeRepository->getActiveStoreByCode($targetStoreCode);
        } catch (StoreIsInactiveException $e) {
            $error = __('Requested store is inactive');
        } catch (NoSuchEntityException $e) {
            $error = __('Requested store is not found');
        }
        if ($error !== null) {
            $this->messageManager->addErrorMessage($error);
        } else {
            $redirectUrl = $this->storeSwitcher->switch($fromStore, $targetStore, $requestedUrlToRedirect);
        }

        $this->getResponse()->setRedirect($redirectUrl);
    }
}
