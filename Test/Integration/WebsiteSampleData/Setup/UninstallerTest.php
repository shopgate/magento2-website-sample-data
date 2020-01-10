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
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Setup\Model\ModuleContext;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;
use Shopgate\WebsiteSampleData\Setup\Installer;
use Shopgate\WebsiteSampleData\Setup\Uninstall;

/**
 * @magentoDbIsolation  enabled
 * @magentoAppArea      adminhtml
 */
class UninstallerTest extends TestCase
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
     * Don't simplify the expected exception
     *
     * @throws LocalizedException
     * @expectedException NoSuchEntityException
     */
    public function testUninstall(): void
    {
        /** @var Installer $installer */
        $installer = $this->objectManager->get(Installer::class);
        $installer->install();

        /** @var StoreManagerInterface $storeManager */
        $storeManager = $this->objectManager->get(StoreManagerInterface::class);
        $stores       = $storeManager->getStores();
        $this->assertCount(3, $storeManager->getWebsites());
        $this->assertCount(5, $storeManager->getGroups());
        $this->assertCount(16, $stores);

        /** @var Uninstall $uninstall */
        $uninstall = $this->objectManager->get(Uninstall::class);
        $setup     = $this->objectManager->get(SchemaSetupInterface::class);
        $context   = $this->objectManager->create(ModuleContext::class, ['version' => '0']);
        $uninstall->uninstall($setup, $context);

        $storeManager->reinitStores(); // clean previously loaded stores
        $this->assertCount(1, $storeManager->getWebsites());
        $this->assertCount(1, $storeManager->getGroups());
        $this->assertCount(1, $storeManager->getStores());

        // Throws no entity exception
        $reader = $this->objectManager->get(ScopeConfigInterface::class);
        $reader->getValue(
            DirectoryHelper::XML_PATH_DEFAULT_LOCALE,
            ScopeInterface::SCOPE_STORES,
            array_pop($stores)->getId()
        );

        // the default website is set again
        $websites = $storeManager->getWebsites();
        $website  = array_shift($websites);
        $this->assertSame('1', $website->getData('is_default'));
    }
}
