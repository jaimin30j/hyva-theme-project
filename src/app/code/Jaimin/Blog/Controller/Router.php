<?php
namespace Jaimin\Blog\Controller;

use Jaimin\Blog\Model\ResourceModel\Post\CollectionFactory;
use Magento\Framework\App\Action\Forward;
use Magento\Framework\App\ActionFactory;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\RouterInterface;
use Magento\Framework\Url;

class Router implements RouterInterface
{
    public function __construct(
        private readonly ActionFactory     $actionFactory,
        private readonly CollectionFactory $collectionFactory
    ) {}

    public function match(RequestInterface $request): ?ActionInterface
    {
        $identifier = trim($request->getPathInfo(), '/');

        // ── Guard 1: must start with "blog/" ─────────────────────────────────
        // Handles: /blog/my-slug → passes
        // Skips:   /catalog/product → skips immediately
        if (!str_starts_with($identifier, 'blog/') && $identifier !== 'blog') {
            return null;
        }

        // ── Guard 2: already a valid controller path ──────────────────────────
        // After our Forward, Magento re-dispatches with pathInfo = /blog/post/view
        // That matches "blog/" above, but we must NOT process it again.
        // Mageplaza solves this by checking routeSize — a valid controller path
        // has exactly 3 parts (module/controller/action).
        // Our slug paths always have exactly 2 parts: blog/my-slug.
        $routePath = explode('/', $identifier);
        $routeSize = count($routePath);

        // More than 2 parts means it's already a controller path (blog/post/view)
        // or something we don't handle — let the standard router take it.
        if ($routeSize > 2) {
            return null;
        }

        // ── Extract slug ──────────────────────────────────────────────────────
        // $routePath[0] = 'blog', $routePath[1] = 'my-post-slug'
        $urlKey = $routePath[1] ?? '';

        // /blog alone → list page, handled by Controller/Index/Index
        if ($urlKey === '') {
            return null;
        }

        // ── Database lookup ───────────────────────────────────────────────────
        $post = $this->findPostByUrlKey($urlKey);
    
        if (!$post) {
            return null;
        }

        // ── Rewrite the request ───────────────────────────────────────────────
        // Order matters — setAlias first, then module/controller/action,
        // then setPathInfo last. This is the Mageplaza-proven sequence.

        // 1. Store the original URL as alias (used by Magento for canonical URLs)
        $request->setAlias(Url::REWRITE_REQUEST_PATH_ALIAS, $identifier);
        
        // 2. Set module/controller/action
        $request->setModuleName('blog')
                ->setControllerName('post')
                ->setActionName('view')
                ->setParam('url_key', $urlKey)
                ->setParam('post_id', (int)$post->getId());

        // 3. THIS IS THE KEY — overwrite pathInfo so on the next router pass
        //    Magento sees /blog/post/view, not /blog/my-post-slug.
        //    The standard router then handles blog/post/view correctly.
        //    Without this, our router matches /blog/my-slug again → loop.
        $request->setPathInfo('/blog/post/view');

        return $this->actionFactory->create(Forward::class);
    }

    private function findPostByUrlKey(string $urlKey): ?\Jaimin\Blog\Model\Post
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('url_key', ['eq' => $urlKey])
                   ->addFieldToFilter('status',  ['eq' => 1])
                   ->setPageSize(1);

        /** @var \Jaimin\Blog\Model\Post $post */
        $post = $collection->getFirstItem();

        return $post->getId() ? $post : null;
    }
}