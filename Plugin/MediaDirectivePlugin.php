<?php
namespace Gumlet\Image\Plugin;

use Magento\Cms\Model\Template\Filter;

/**
 * Plugin to add width parameters to WYSIWYG media directive images
 */
class MediaDirectivePlugin
{

    
    /** @var array */
    private $processedUrls = [];

    public function __construct() {}


    /**
     * Add width parameter to media directive URLs
     *
     * @param Filter $subject
     * @param string $result
     * @param array $construction
     * @return string
     */
    public function afterMediaDirective(Filter $subject, $result, $construction)
    {
        try {
            if ($this->isImageUrl($result)) {
                
                $originalUrl = $this->cleanUrl($result);
                $urlHash = hash('sha256', $originalUrl);
                
                if (isset($this->processedUrls[$urlHash])) {
                    return $this->processedUrls[$urlHash];
                }
                
                // Check if width parameter already exists in the URL
                if (strpos($result, 'w=') !== false) {
                    $this->processedUrls[$urlHash] = $result;
                    return $result;
                }
                
                $width = $this->getMediaImageWidth($construction);
                if ($width) {
                    $glue = parse_url($result, PHP_URL_QUERY) ? '&' : '?';
                    $result .= $glue . 'w=' . $width;
                    $this->processedUrls[$urlHash] = $result;
                } else {
                    $this->processedUrls[$urlHash] = $result;
                }
            }
        } catch (\Exception $e) {}

        return $result;
    }

    /**
     * Clean URL by removing existing width parameters
     *
     * @param string $url
     * @return string
     */
    private function cleanUrl(string $url): string
    {
        // Remove any existing w= parameters
        $cleanUrl = preg_replace('/[?&]w=\d+/', '', $url);
        // Remove trailing ? or & if they exist
        $cleanUrl = rtrim($cleanUrl, '?&');
        return $cleanUrl;
    }

    /**
     * Check if the URL is for an image
     *
     * @param string $url
     * @return bool
     */
    private function isImageUrl(string $url): bool
    {
        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp'];
        $extension = strtolower(pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));
        return in_array($extension, $imageExtensions);
    }

    /**
     * Determine appropriate width for media directive images
     *
     * @param array $construction
     * @return int|null
     */
    private function getMediaImageWidth(array $construction): ?int
    {
        $url = $construction[2] ?? '';
        
        // Pattern-based width determination
        $filename = strtolower(basename($url));
        $path = strtolower($url);
        
        // Check for size indicators in filename or path
        if (strpos($filename, 'banner') !== false || 
            strpos($filename, 'hero') !== false ||
            strpos($path, 'banner') !== false ||
	    strpos($path, 'wysiwyg/home/home-main') !== false ||
            strpos($path, 'hero') !== false) {
            return 1440; // Large banner/hero images
        }
        
        if (strpos($filename, 'thumb') !== false || 
            strpos($filename, 'small') !== false ||
            strpos($path, 'thumb') !== false) {
            return 360; // Thumbnail images
        }
        
        if (strpos($filename, 'medium') !== false ||
            strpos($path, 'medium') !== false) {
            return 600; // Medium images
        }
        
        if (strpos($filename, 'large') !== false ||
            strpos($path, 'large') !== false) {
            return 1080; // Large images
        }
        
        // Check for specific directories
        if (strpos($path, 'wysiwyg/homepage') !== false ||
            strpos($path, 'wysiwyg/home') !== false) {
            return 960; // Homepage images
        }
        
        if (strpos($path, 'wysiwyg/category') !== false) {
            return 600; // Category page images
        }
        
        if (strpos($path, 'wysiwyg/product') !== false) {
            return 480; // Product-related images
        }
        
        // Default width for WYSIWYG images
        return 960;
    }
}
