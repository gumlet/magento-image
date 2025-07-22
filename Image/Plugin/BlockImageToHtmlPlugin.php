<?php
namespace Gumlet\Image\Plugin;

use Magento\Catalog\Block\Product\Image as BlockImage;
use Magento\Framework\View\ConfigInterface;
use Psr\Log\LoggerInterface;

class BlockImageToHtmlPlugin
{
    /** @var ConfigInterface */
    private $viewConfig;
    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        ConfigInterface $viewConfig,
        LoggerInterface $logger
    ) {
        $this->viewConfig = $viewConfig;
        $this->logger     = $logger;
    }

    /**
     * After the block has rendered its HTML, inject w/h on every product‑image <img>.
     *
     * @param BlockImage $subject
     * @param string     $html   The full <img> tag markup (and surrounding HTML)
     * @return string
     */
    public function afterToHtml(BlockImage $subject, $html)
    {
        // 1) determine width/height exactly the same way as before
        $w    = $subject->getWidth();
        $role = $subject->getImageType();

        if ((!$w) && $role) {
            $cfg = $this->viewConfig
                        ->getViewConfig()
                        ->getMediaGalleryImageData($role) ?: [];
            if (!$w && !empty($cfg['width']))  { $w = (int)$cfg['width']; }
        }

        if (!$w) {
            // nothing to do
            return $html;
        }

        // 2) regex‑replace every src=".../media/catalog/product/..." in the HTML
        $newHtml = preg_replace_callback(
            '/src="([^"]*\/media\/catalog\/product\/[^"]+)"/i',
            function ($m) use ($w) {
                $url = $m[1];
                $glue = (strpos($url, '?') === false) ? '?' : '&';
                $new  = $url . $glue . 'w=' . $w;
                return 'src="' . $new . '"';
            },
            $html
        );

        return $newHtml;
    }
}

