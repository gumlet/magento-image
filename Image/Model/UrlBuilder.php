<?php
namespace Gumlet\Image\Model;

use Magento\Framework\View\ConfigInterface;
use Magento\Catalog\Model\Product\Image\ParamsBuilder;
use Magento\Catalog\Model\View\Asset\ImageFactory;
use Magento\Framework\View\Asset\Repository as AssetRepository;
use Magento\Catalog\Model\View\Asset\PlaceholderFactory;
use Psr\Log\LoggerInterface;
use Magento\Catalog\Model\Product\Image\UrlBuilder as CoreUrlBuilder;

class UrlBuilder extends CoreUrlBuilder
{
    /** @var ConfigInterface */
    private $viewConfig;

    /** @var LoggerInterface */
    private $logger;

    /**
     * The first four arguments must match the core constructor exactly.
     *
     * @param ConfigInterface    $presentationConfig
     * @param ParamsBuilder      $imageParamsBuilder
     * @param ImageFactory       $viewAssetImageFactory
     * @param PlaceholderFactory $placeholderFactory
     * @param LoggerInterface    $logger
     */
    public function __construct(
        ConfigInterface    $presentationConfig,
        ParamsBuilder      $imageParamsBuilder,
        ImageFactory       $viewAssetImageFactory,
        PlaceholderFactory $placeholderFactory,
        LoggerInterface    $logger
    ) {
        parent::__construct(
            $presentationConfig,
            $imageParamsBuilder,
            $viewAssetImageFactory,
            $placeholderFactory
        );
        $this->viewConfig = $presentationConfig;
        $this->logger     = $logger;
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

            // 3) If width is found, append parameter
            if ($width) {
                $glue = parse_url($url, PHP_URL_QUERY) ? '&' : '?';
                $url .= $glue . 'w=' . $width;
            } else {
                $this->logger->info('[Gumlet] No width found for image area', [
                    'imageDisplayArea' => $imageDisplayArea,
                    'baseFilePath' => $baseFilePath // Added for better debugging
                ]);
            }
        } catch (\Exception $e) {
            $this->logger->error('[Gumlet] Error getting image width: ' . $e->getMessage());
        }

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

            // Map image display areas to their corresponding size variables
            $sizeMapping = [
                // Product page images (carousel/gallery)
                'product_page_image_small' => 'product_base_image_size',
                'product_page_image_medium' => 'product_base_image_size',
                'product_page_image_large' => 'product_zoom_image_size',

                // Main product images - added more variations
                'product_base_image' => 'product_base_image_size',
                'product_image' => 'product_base_image_size',
                'product_page_main_image' => 'product_base_image_size',
                'product_page_main_image_default' => 'product_base_image_size',

                // Category pages
                'category_page_list' => 'product_list_image_size',
                'category_page_grid' => 'product_list_image_size',
                'category_page_grid_narrow' => 'product_list_image_size',
                'category_page_grid_wide' => 'product_base_image_size',

                // Thumbnail and small images
                'product_small_image' => 'product_small_image_sidebar_size',
                'product_thumbnail_image' => 'product_base_image_icon_size',
                'product_swatch_image' => 'product_base_image_icon_size',

                // Related/upsell products
                'related_products_list' => 'product_list_image_size',
                'upsell_products_list' => 'product_list_image_size',
                'crosssell_products_list' => 'product_list_image_size',
            ];

            // Custom width overrides - expanded with more possible area names
            $customWidths = [
                // Product page main images
                'product_page_image_small' => 88,
                'product_page_main_image' => 700,
                'product_page_main_image_default' => 700,
                'product_base_image' => 700,
                
                // Common variations that might be used on product pages
                'product_image' => 700,
                'product_page_image_medium' => 700,
                'product_page_image_large' => 700,
                
                // Gallery and carousel images
                'product_gallery' => 700,
                'product_gallery_main' => 700,
                'product_media_main' => 700,
                'product_media_gallery' => 700,
            ];

            // Check for custom widths first
            if (isset($customWidths[$imageDisplayArea])) {
                return $customWidths[$imageDisplayArea];
            }

            // Get the size variable name for this image display area
            $sizeVarName = $sizeMapping[$imageDisplayArea] ?? null;

            if ($sizeVarName && isset($catalogVars[$sizeVarName])) {
                $size = (int)$catalogVars[$sizeVarName];

                // Override specific sizes that are too small for main product images
                if ($imageDisplayArea === 'product_base_image' && $size < 500) {
                    $size = 700; // Force larger size for main product images
                }

                return $size;
            }

            // Fallback: try to find a direct match or similar name
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

            // Final fallback based on image area name patterns
            $defaultWidth = $this->getDefaultWidthByPattern($imageDisplayArea);
            if ($defaultWidth) {
                return $defaultWidth;
            }

            $this->logger->warning('[Gumlet] No width mapping found', [
                'imageDisplayArea' => $imageDisplayArea,
                'availableVars' => array_keys($catalogVars),
                'availableCustomWidths' => array_keys($customWidths),
                'availableSizeMappings' => array_keys($sizeMapping)
            ]);

        } catch (\Exception $e) {
            $this->logger->error('[Gumlet] Error getting image width: ' . $e->getMessage());
        }

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
