
# Bluenty Restore Order Module

## Overview
The Bluenty Restore Order module for Magento 2 provides functionality to restore orders that have been canceled. This module adds a button with a dropdown menu to the order view in the Magento 2 admin panel, allowing administrators to easily restore these orders.

## Features
- Restore orders that were canceled.
- Supports multiple languages with translation files, including Spanish (ES_es).
- Easily accessible from the order view in the admin panel with a single button when the order is canceled.

## Installation

1. **Upload the Module:**
   - Place the module files in `app/code/Bluenty/RestoreOrder/`.

2. **Enable the Module:**
   Run the following commands in the Magento root directory:
   ```bash
   php bin/magento module:enable Bluenty_RestoreOrder
   php bin/magento setup:upgrade
   php bin/magento setup:di:compile
   php bin/magento cache:flush
   ```

3. **Translation:**
   - Spanish (ES_es) translation is available by default. To add more languages, create appropriate translation files in the `i18n` folder.

## Usage

1. **Access Order View:**
   - Go to the Magento 2 admin panel, and view an order that is canceled.

2. **Restore Order:**
   - You will see a "Restore Order" button with a dropdown menu. Select either "Restore Order (Payment Failure)" or "Restore Canceled Order" based on the scenario.

3. **Confirmation:**
   - The order will be restored and a confirmation message will be displayed.

## Troubleshooting

- If you encounter a 404 error after installing the module, ensure that you have cleared the cache and recompiled Magento using the commands mentioned above.
- Check `var/log/system.log` and `var/log/exception.log` for any errors if the module does not behave as expected.

## License
This module is licensed under the GNU Affero General Public License v3.0. See the [LICENSE](LICENSE) file for more details.

