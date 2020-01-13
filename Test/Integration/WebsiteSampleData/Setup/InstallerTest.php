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

namespace Shopgate\WebsiteSampleData\Test\Integration\Setup;

use Magento\Directory\Helper\Data as DirectoryHelper;
use Magento\Directory\Model\Currency;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;
use Shopgate\WebsiteSampleData\Setup\Installer;

/**
 * @magentoDbIsolation  enabled
 * @magentoAppArea      adminhtml
 */
class InstallerTest extends TestCase
{
    /** @var ObjectManager $objectManager */
    protected $objectManager;

    /**
     * Basic setup
     */
    public function setUp()
    {
        $this->objectManager = Bootstrap::getObjectManager();
    }

    /**
     * @throws LocalizedException
     */
    public function testInstall(): void
    {
        /** @var Installer $installer */
        $installer = $this->objectManager->get(Installer::class);
        $installer->install();

        /** @var StoreManagerInterface $storeManager */
        $storeManager = $this->objectManager->get(StoreManagerInterface::class);
        $this->assertCount(16, $storeManager->getStores());
        $this->assertCount(3, $storeManager->getWebsites());
        $this->assertCount(5, $storeManager->getGroups());
        $laptopStore = $storeManager->getStore('store_laptop_de');
        /** @var ScopeConfigInterface $reader */
        $reader            = $this->objectManager->get(ScopeConfigInterface::class);
        $laptopStoreLocale = $reader->getValue(
            DirectoryHelper::XML_PATH_DEFAULT_LOCALE,
            ScopeInterface::SCOPE_STORES,
            $laptopStore->getId()
        );
        $this->assertSame('de_DE', $laptopStoreLocale);

        $phoneWebsite         = $storeManager->getWebsite('web_phone');
        $phoneWebsiteCurrency = $reader->getValue(
            Currency::XML_PATH_CURRENCY_BASE,
            ScopeInterface::SCOPE_WEBSITES,
            $phoneWebsite->getId()
        );
        $this->assertSame('RUB', $phoneWebsiteCurrency);
    }
}
