# Gumlet Image Magento 2 Module

Automatically optimize images in Magento using Gumlet CDN.

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

## Changing Secure Base URL for Media to Gumlet

To deliver all Magento media assets (images, etc.) via Gumlet, update your secure base media URL:

1. **Login to Magento Admin**
2. Go to **Stores > Configuration > General > Web**
3. Expand the **Base URLs (Secure)** section
4. Find **Base URL for User Media Files**
5. Change the value from:
   ```
   {{secure_base_url}}media/
   ```
   to your Gumlet media URL, e.g.:
   ```
   https://your-gumlet-domain.gumlet.io/media/
   ```
6. Click **Save Config**
7. Flush cache: `bin/magento cache:flush`

**Note:**
- This will route all media requests through Gumlet. Make sure your Gumlet origin is set to your Magento media directory.
- If you want to revert, simply change the value back to `{{secure_base_url}}media/`.

## Support
For issues, please open an issue on the repository or contact Gumlet support.

## License
MIT
