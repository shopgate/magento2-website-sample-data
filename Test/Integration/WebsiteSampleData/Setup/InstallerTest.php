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

use Magento\Framework\Exception\LocalizedException;
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
        $this->assertTrue(true, 'Just checking that there are no exceptions thrown');
    }
}
