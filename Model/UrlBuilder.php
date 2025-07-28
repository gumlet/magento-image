<?php
namespace Gumlet\Image\Model;

use Magento\Framework\View\ConfigInterface;
use Magento\Catalog\Model\Product\Image\ParamsBuilder;
use Magento\Catalog\Model\View\Asset\ImageFactory;
use Magento\Framework\View\Asset\Repository as AssetRepository;
use Magento\Catalog\Model\View\Asset\PlaceholderFactory;
use Magento\Catalog\Model\Product\Image\UrlBuilder as CoreUrlBuilder;

class UrlBuilder extends CoreUrlBuilder
{
    /** @var ConfigInterface */
    private $viewConfig;



    /**
     * The first four arguments must match the core constructor exactly.
     *
     * @param ConfigInterface    $presentationConfig
     * @param ParamsBuilder      $imageParamsBuilder
     * @param ImageFactory       $viewAssetImageFactory
     * @param PlaceholderFactory $placeholderFactory
     */
    public function __construct(
        ConfigInterface    $presentationConfig,
        ParamsBuilder      $imageParamsBuilder,
        ImageFactory       $viewAssetImageFactory,
        PlaceholderFactory $placeholderFactory,
        ) {
        parent::__construct(
            $presentationConfig,
            $imageParamsBuilder,
            $viewAssetImageFactory,
            $placeholderFactory
        );
        $this->viewConfig = $presentationConfig;
    }

    /**
     * Build the URL via Magento, then append ?w=… from view.xml.
     *
     * @param string $baseFilePath
     * @param string $imageDisplayArea
     * @return string
     */
    public function getUrl(string $baseFilePath, string $imageDisplayArea): string
    {
        // 1) Magento's URL (cache-path or placeholder)
        $url = parent::getUrl($baseFilePath, $imageDisplayArea);

        try {
            // 2) Get width from your theme's view.xml
            $width = $this->getImageWidthFromViewXml($imageDisplayArea);

            if ($width) {
                $glue = parse_url($url, PHP_URL_QUERY) ? '&' : '?';
                $url .= $glue . 'w=' . $width;
            }
        } catch (\Exception $e) {}

        return $url;
    }

    /**
     * Get image width from view.xml based on image display area
     *
     * @param string $imageDisplayArea
     * @return int|null
     */
    private function getImageWidthFromViewXml(string $imageDisplayArea): ?int
    {
        try {
            $viewConfigData = $this->viewConfig->getViewConfig();
            $catalogVars = $viewConfigData->getVars('Magento_Catalog');

            $sizeMapping = [
                'product_page_image_small' => 'product_base_image_size',
                'product_page_image_medium' => 'product_base_image_size',
                'product_page_image_large' => 'product_zoom_image_size',
                'product_base_image' => 'product_base_image_size',
                'product_image' => 'product_base_image_size',
                'product_page_main_image' => 'product_base_image_size',
                'product_page_main_image_default' => 'product_base_image_size',
                'category_page_list' => 'product_list_image_size',
                'category_page_grid' => 'product_list_image_size',
                'category_page_grid_narrow' => 'product_list_image_size',
                'category_page_grid_wide' => 'product_base_image_size',
                'product_small_image' => 'product_small_image_sidebar_size',
                'product_thumbnail_image' => 'product_base_image_icon_size',
                'product_swatch_image' => 'product_base_image_icon_size',
                'related_products_list' => 'product_list_image_size',
                'upsell_products_list' => 'product_list_image_size',
                'crosssell_products_list' => 'product_list_image_size',
            ];

            $customWidths = [
                'product_page_image_small' => 88,
                'product_page_main_image' => 700,
                'product_page_main_image_default' => 700,
                'product_base_image' => 700,
                'product_image' => 700,
                'product_page_image_medium' => 700,
                'product_page_image_large' => 700,
                'product_gallery' => 700,
                'product_gallery_main' => 700,
                'product_media_main' => 700,
                'product_media_gallery' => 700,
            ];

            if (isset($customWidths[$imageDisplayArea])) {
                return $customWidths[$imageDisplayArea];
            }

            $sizeVarName = $sizeMapping[$imageDisplayArea] ?? null;

            if ($sizeVarName && isset($catalogVars[$sizeVarName])) {
                $size = (int)$catalogVars[$sizeVarName];

                if ($imageDisplayArea === 'product_base_image' && $size < 500) {
                    $size = 700;
                }

                return $size;
            }

            $possibleKeys = [
                $imageDisplayArea . '_size',
                str_replace(['_page_', '_category_'], '_', $imageDisplayArea) . '_size',
            ];

            foreach ($possibleKeys as $key) {
                if (isset($catalogVars[$key])) {
                    $size = (int)$catalogVars[$key];
                    return $size;
                }
            }

            $defaultWidth = $this->getDefaultWidthByPattern($imageDisplayArea);
            if ($defaultWidth) {
                return $defaultWidth;
            }
        } catch (\Exception $e) {}

        return null;
    }

    /**
     * Get default width based on image area name patterns
     *
     * @param string $imageDisplayArea
     * @return int|null
     */
    private function getDefaultWidthByPattern(string $imageDisplayArea): ?int
    {
        // Pattern-based defaults for common image contexts
        if (strpos($imageDisplayArea, 'page_image') !== false) {
            // Product page images should be larger
            if (strpos($imageDisplayArea, 'large') !== false) {
                return 700;
            }
            if (strpos($imageDisplayArea, 'medium') !== false) {
                return 700;
            }
            if (strpos($imageDisplayArea, 'small') !== false) {
                return 275;
            }
        }

        // Main product image patterns
        if (strpos($imageDisplayArea, 'base_image') !== false || 
            strpos($imageDisplayArea, 'main_image') !== false ||
            strpos($imageDisplayArea, 'product_image') !== false) {
            return 700;
        }

        if (strpos($imageDisplayArea, 'category') !== false) {
            return 240;
        }

        if (strpos($imageDisplayArea, 'thumbnail') !== false || strpos($imageDisplayArea, 'swatch') !== false) {
            return 88;
        }

        if (strpos($imageDisplayArea, 'related') !== false || strpos($imageDisplayArea, 'upsell') !== false) {
            return 200;
        }

        // Default for gallery/media images
        if (strpos($imageDisplayArea, 'gallery') !== false || strpos($imageDisplayArea, 'media') !== false) {
            return 700;
        }

        return null;
    }
}
