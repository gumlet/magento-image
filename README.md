# Gumlet_Image Magento 2 Module

Automatically appends width (`w`) parameters to product and media image URLs in Magento, enabling optimized image delivery for CDNs like Gumlet, Imgix, etc.

## Features
- Appends `w` (width) parameter to all product and media image URLs
- Works with product images, CMS, email, and widget images
- Uses theme configuration for width detection
- Prevents duplicate URL processing

## Installation

### Composer (Recommended)
```
composer require gumlet/module-image
```

### Manual
1. Copy the `Image` folder to `app/code/Gumlet/` in your Magento installation.
2. Run:
   ```
   bin/magento setup:upgrade
   bin/magento cache:flush
   ```

## Usage
No manual configuration required. All product and media images will automatically have a `w` parameter appended.

## Configuration
- Width is determined from the theme's `view.xml` or block context.
- No admin configuration required.

## Compatibility
- Magento 2.3 and above

## Support
For issues, please open an issue on the repository or contact Gumlet support.

## License
MIT
