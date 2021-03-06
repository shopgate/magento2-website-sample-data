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
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Module\Setup;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UninstallInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Model\ResourceModel\Website as WebsiteResource;
use Magento\Store\Model\StoreManagerInterface;

/**
 * @codeCoverageIgnore
 */
class Uninstall implements UninstallInterface
{
    /** * @var StoreManagerInterface */
    private $storeManager;
    /** * @var WebsiteResource */
    private $websiteResource;

    /**
     * @param StoreManagerInterface $storeManager
     * @param WebsiteResource       $websiteResource
     */
    public function __construct(StoreManagerInterface $storeManager, WebsiteResource $websiteResource)
    {
        $this->storeManager    = $storeManager;
        $this->websiteResource = $websiteResource;
    }

    /**
     * Remove data that was created during module installation.
     *
     * @param SchemaSetupInterface|Setup $setup
     * @param ModuleContextInterface     $context
     *
     * @return void
     * @throws AlreadyExistsException
     */
    public function uninstall(SchemaSetupInterface $setup, ModuleContextInterface $context): void
    {
        $uninstaller       = $setup;
        $defaultConnection = $uninstaller->getConnection(ResourceConnection::DEFAULT_CONNECTION);

        $websiteCodes = ['web_computer', 'web_phone'];
        $escapedCodes = $defaultConnection->quote($websiteCodes);
        $websites     = $this->getCreatedWebsites($websiteCodes);
        $stores       = $this->getCreatedStores($websites);
        $storeIds     = $this->getKeys($stores);
        $websiteIds   = $this->getKeys($websites);
        $websiteTable = $uninstaller->getTable('store_website');
        $configTable  = $uninstaller->getTable('core_config_data');
        $defaultConnection->delete($websiteTable, "`code` IN ($escapedCodes)");
        $defaultConnection->delete($configTable, "`scope_id` IN ($storeIds) AND `scope`='stores'");
        $defaultConnection->delete($configTable, "`scope_id` IN ($websiteIds) AND `scope`='websites'");

        // set the default website back
        $websites = $this->storeManager->getWebsites();
        $website  = array_shift($websites);
        $this->websiteResource->save($website->setData('is_default', 1));
    }

    /**
     * Get stores belonging to the new websites
     *
     * @param WebsiteInterface[] $websites
     *
     * @return StoreInterface[]
     */
    public function getCreatedStores(array $websites): array
    {
        return array_map(
            static function (StoreInterface $store) use ($websites) {
                return isset($websites[$store->getWebsiteId()]);
            },
            $this->storeManager->getStores()
        );
    }

    /**
     * Get newly created websites
     *
     * @param string[] $websiteCodes
     *
     * @return WebsiteInterface[]
     */
    private function getCreatedWebsites(array $websiteCodes): array
    {
        return array_map(
            static function (WebsiteInterface $website) use ($websiteCodes) {
                return in_array($website->getCode(), $websiteCodes, true);
            },
            $this->storeManager->getWebsites()
        );
    }

    /**
     * @param WebsiteInterface[]|StoreInterface[] $entities
     *
     * @return string
     */
    private function getKeys(array $entities): string
    {
        return implode(',', array_keys($entities));
    }
}
