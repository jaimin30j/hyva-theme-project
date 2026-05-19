<?php
namespace Jaimin\Blog\Controller\Post;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\ForwardFactory;
use Magento\Framework\View\Result\PageFactory;

class View implements HttpGetActionInterface
{
    public function __construct(
        private readonly PageFactory      $pageFactory,
        private readonly RequestInterface $request,
        private readonly ForwardFactory   $forwardFactory
    ) {}

    public function execute()
    {
        $urlKey = (string)$this->request->getParam('url_key', '');

        // If no url_key was set by the router, something went wrong
        // Forward to noroute (404)
        if (!$urlKey) {
            return $this->forwardFactory->create()->forward('noroute');
        }

        $page = $this->pageFactory->create();
        $page->getConfig()->getTitle()->set(__('Blog'));

        return $page;
    }
}