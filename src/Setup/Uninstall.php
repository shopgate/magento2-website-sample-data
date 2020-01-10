<?php

/**
 * Copyright Shopgate Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * @author    Shopgate Inc, 804 Congress Ave, Austin, Texas 78701 <interfaces@shopgate.com>
 * @copyright Shopgate Inc
 * @license   http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */

declare(strict_types=1);

namespace Shopgate\WebsiteSampleData\Setup;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Module\Setup;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UninstallInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * @codeCoverageIgnore
 */
class Uninstall implements UninstallInterface
{
    /** * @var StoreManagerInterface */
    private $storeManager;

    public function __construct(StoreManagerInterface $storeManager)
    {
        $this->storeManager = $storeManager;
    }

    /**
     * Remove data that was created during module installation.
     *
     * @param SchemaSetupInterface|Setup $setup
     * @param ModuleContextInterface     $context
     *
     * @return void
     */
    public function uninstall(SchemaSetupInterface $setup, ModuleContextInterface $context): void
    {
        $uninstaller = $setup;
        $defaultConnection = $uninstaller->getConnection(ResourceConnection::DEFAULT_CONNECTION);

        $websiteCodes      = ['web_computer', 'web_phone'];
        $escapedCodes      = $defaultConnection->quote($websiteCodes);
        $storeIds          = $this->getCreatedStoreIds($websiteCodes);
        $websiteTable      = $uninstaller->getTable('store_website');
        $configTable       = $uninstaller->getTable('core_config_data');
        $defaultConnection->delete($websiteTable, "`code` IN ($escapedCodes)");
        $defaultConnection->delete($configTable, "`scope_id` IN ($storeIds)");
    }

    /**
     * @param string[] $websiteCodes
     *
     * @return string id's of newly created stores
     */
    public function getCreatedStoreIds(array $websiteCodes): string
    {
        // Get newly created websites
        $websites = array_map(
            static function (WebsiteInterface $website) use ($websiteCodes) {
                return in_array($website->getCode(), $websiteCodes, true);
            },
            $this->storeManager->getWebsites()
        );
        // Get stores belonging to the new websites
        $stores = array_map(
            static function (StoreInterface $store) use ($websites) {
                return isset($websites[$store->getWebsiteId()]);
            },
            $this->storeManager->getStores()
        );

        return implode(',', array_keys($stores));
    }
}
