# Prices Generator

**Description:**  
The "Prices Generator" plugin for WooCommerce allows you to automatically generate and update product prices based on a predefined equation. This plugin is designed to work with products by utilizing various cost parameters and calculating prices accordingly. It also supports excluding specific products from price generation.

## Features:

- **Bulk Action for Price Generation:** Allows you to apply price generation to multiple products at once.
- **Individual Product Price Generation:** Provides an option to generate price for individual products via the row actions in the product list.
- **Custom Price Calculation:** Uses a set of predefined cost parameters to calculate product prices.
- **Exclusion Management:** Supports excluding specific products from price generation.
- **Settings Page:** Offers a settings page to configure cost parameters and manage excluded products.

## Usage:

### 1. Configuring Settings

- **Navigate to Settings Page:**
  - Go to `Prices Generator` in the WordPress admin sidebar.

- **Configure Cost Parameters:**
  - Fill in the fields with the appropriate values for:
    - Paper price
    - Paper printing price
    - Cover price
    - Cover printing price
    - Cover lamination
    - Shipping variance price
    - Book packaging cost
    - Cover design cost
    - Book gluing price
    - Book cutting price
    - Profit fixed
    - Profit ratio

- **Manage Excluded Products:**
  - Use the product search box to select products that should be excluded from the price generation process.

- **Save Changes:**
  - Click the "Save Changes" button to store your settings.

### 2. Generating Prices

- **Bulk Action:**
  - Go to the Products page in the WordPress admin area.
  - Select the products you want to update.
  - Choose "Generate Price(s)" from the Bulk Actions dropdown and apply.

- **Individual Product:**
  - On the Products page, find the product you want to update.
  - Click the "Generate Price" link in the row actions for that product.

## Admin Notices

- After generating prices, a success message will be displayed showing the number of products updated.
- If there are any excluded products, they will be listed in the notice.

## Customization:

- **Parameters and Calculations:**
  - You can modify the cost parameters and calculation logic in the plugin code to fit your specific requirements.

- **Styling:**
  - Customize the appearance of the settings page by modifying the CSS in the `pricesgenerator_settings_page` function.

## Changelog:

**Version 1.1.1:**
- Fixed issues with price calculation and product exclusion.
- Enhanced AJAX functionality for product search.

## Credits:

- **Author:** Yousseif Ahmed

## License:

This plugin is licensed under the [GNU General Public License v3.0](https://www.gnu.org/licenses/gpl-3.0.html).

For more information, visit the [WordPress Plugin Developer Handbook](https://developer.wordpress.org/plugins/).


---

**Note:** This README is a sample and should be customized based on the actual details and updates of your plugin. Make sure to adjust the URLs, author details, and any specific instructions relevant to your plugin.
