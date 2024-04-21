<?php

namespace Custom\LatestProducts\Block;

use Exception;
use Magento\Catalog\Block\Product\AbstractProduct;
use Magento\Catalog\Block\Product\Context;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Catalog\Pricing\Price\FinalPrice;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Pricing\Render;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\Url\EncoderInterface;
use Magento\Framework\View\LayoutFactory;
use Magento\GroupedProduct\Model\Product\Type\Grouped;
use Magento\Widget\Block\BlockInterface;

/**
 * Class AbstractLatestProducts
 * @package Custom\LatestProducts\Block
 */
abstract class AbstractLatestProducts extends AbstractProduct implements BlockInterface, IdentityInterface
{
    /**
     * @var DateTime
     */
    protected $_date;
  
    /**
     * @var CollectionFactory
     */
    protected $_productCollectionFactory;
    /**
     * @var Visibility
     */
    protected $_catalogProductVisibility;
    /**
     * @var HttpContext
     */
    protected $httpContext;
    /**
     * @var EncoderInterface|null
     */
    protected $urlEncoder;
    /**
     * @var Grouped
     */
    protected $grouped;
    /**
     * @var Configurable
     */
    protected $configurable;
    /**
     * @var
     */
    protected $rendererListBlock;
    /**
     * @var
     */
    private $priceCurrency;
    /**
     * @var LayoutFactory
     */
    private $layoutFactory;

    /**
     * AbstractLatestProducts constructor.
     *
     * @param Context $context
     * @param CollectionFactory $productCollectionFactory
     * @param Visibility $catalogProductVisibility
     * @param DateTime $dateTime
     * @param Data $helperData
     * @param HttpContext $httpContext
     * @param EncoderInterface $urlEncoder
     * @param Grouped $grouped
     * @param Configurable $configurable
     * @param LayoutFactory $layoutFactory
     * @param array $data
     */
    public function __construct(
        Context $context,
        CollectionFactory $productCollectionFactory,
        Visibility $catalogProductVisibility,
        DateTime $dateTime,
        HttpContext $httpContext,
        EncoderInterface $urlEncoder,
        Grouped $grouped,
        Configurable $configurable,
        LayoutFactory $layoutFactory,
        array $data = []
    ) {
        $this->_productCollectionFactory = $productCollectionFactory;
        $this->_catalogProductVisibility = $catalogProductVisibility;
        $this->_date                     = $dateTime;
        $this->httpContext               = $httpContext;
        $this->urlEncoder                = $urlEncoder;
        $this->grouped                   = $grouped;
        $this->configurable              = $configurable;
        $this->layoutFactory             = $layoutFactory;

        parent::__construct($context, $data);
    }

    /**
     * Get Key pieces for caching block content
     *
     * @return array
     * @throws NoSuchEntityException
     */
    public function getCacheKeyInfo()
    {
        return [
            'CUSTOM_LATEST_PRODUCT',
            $this->getPriceCurrency()->getCurrency()->getCode(),
            $this->_storeManager->getStore()->getId(),
            $this->httpContext->getValue(\Magento\Customer\Model\Context::CONTEXT_GROUP)
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function _construct()
    {
        parent::_construct();

        $this->addData([
            'cache_lifetime' => 86400,
            'cache_tags'     => [Product::CACHE_TAG]
        ]);

        // $this->setTemplate('Mageplaza_Productslider::productslider.phtml');
    }

    /**
     * Get post parameters.
     *
     * @param Product $product
     *
     * @return array
     */
    public function getAddToCartPostParams(Product $product)
    {
        $url = $this->getAddToCartUrl($product);

        return [
            'action' => $url,
            'data'   => [
                'product'                               => $product->getEntityId(),
                ActionInterface::PARAM_NAME_URL_ENCODED => $this->urlEncoder->encode($url),
            ]
        ];
    }

    /**
     * @return bool
     */
    public function canShowPrice()
    {
        return in_array(Additional::SHOW_PRICE, $this->getDisplayAdditional(), true);
    }


    /**
     * @param Product $product
     * @param null $priceType
     * @param string $renderZone
     * @param array $arguments
     *
     * @return string
     * @throws LocalizedException
     */
    public function getProductPriceHtml(
        Product $product,
        $priceType = null,
        $renderZone = Render::ZONE_ITEM_LIST,
        array $arguments = []
    ) {
        if (!isset($arguments['zone'])) {
            $arguments['zone'] = $renderZone;
        }
        $arguments['price_id']              = isset($arguments['price_id'])
            ? $arguments['price_id']
            : 'old-price-' . $product->getId() . '-' . $priceType;
        $arguments['include_container']     = isset($arguments['include_container'])
            ? $arguments['include_container']
            : true;
        $arguments['display_minimal_price'] = isset($arguments['display_minimal_price'])
            ? $arguments['display_minimal_price']
            : true;

        /** @var Render $priceRender */
        $priceRender = $this->getPriceRender();
       
        if (!$priceRender) {
            $priceRender = $this->getLayout()->createBlock(
                Render::class,
                'product.price.render.default',
                ['data' => ['price_render_handle' => 'catalog_product_prices']]
            );
        }

        return $priceRender->render(
            FinalPrice::PRICE_CODE,
            $product,
            $arguments
        );
    }

    /**
     * @return bool|\Magento\Framework\View\Element\BlockInterface
     * @throws LocalizedException
     */
    protected function getPriceRender()
    {
        return $this->getLayout()->getBlock('product.price.render.default');
    }

    /**
     * @return mixed
     */
    private function getPriceCurrency()
    {
        if ($this->priceCurrency === null) {
            $this->priceCurrency = ObjectManager::getInstance()
                ->get(PriceCurrencyInterface::class);
        }

        return $this->priceCurrency;
    }

    /**
     * @return bool
     */
    public function canShowAddToCart()
    {
        return in_array(Additional::SHOW_CART, $this->getDisplayAdditional(), true);
    }

    /**
     * Get Store Id
     *
     * @return int
     */
    public function getStoreId()
    {
        return $this->_storeManager->getStore()->getId();
    }

    public function getIdentities()
    {
        $identities = [];
        if ($this->getProductCollection()) {
            foreach ($this->getProductCollection() as $product) {
                if ($product instanceof IdentityInterface) {
                    $identities += $product->getIdentities();
                }
            }
        }

        return $identities ?: [Product::CACHE_TAG];
    }

    /**
     * @return mixed
     */
    abstract public function getProductCollection();


    /**
     * @return bool|\Magento\Framework\View\Element\BlockInterface|\Magento\Framework\View\Element\RendererList
     * @throws LocalizedException
     */
    protected function getDetailsRendererList()
    {
        if (empty($this->rendererListBlock)) {
            $layout = $this->layoutFactory->create(['cacheable' => false]);
            $layout->getUpdate()->addHandle('catalog_widget_product_list')->load();
            $layout->generateXml();
            $layout->generateElements();

            $this->rendererListBlock = $layout->getBlock('category.product.type.widget.details.renderers');
        }

        return $this->rendererListBlock;
    }
}
