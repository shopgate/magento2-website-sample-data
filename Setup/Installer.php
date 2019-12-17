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
 * @copyright 2019 Shopgate Inc
 * @license   http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */

declare(strict_types=1);

namespace Shopgate\WebsiteSampleData\Setup;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Setup;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Shopgate\WebsiteSampleData\Model\Website;

class Installer implements Setup\SampleData\InstallerInterface
{
    /** @var Website $website */
    protected $website;
    /** @var StoreManagerInterface */
    private $storeManager;

    /**
     * @param Website               $website
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(Website $website, StoreManagerInterface $storeManager = null)
    {
        $this->website      = $website;
        $this->storeManager = $storeManager ? : ObjectManager::getInstance()->get(StoreManagerInterface::class);
    }

    /**
     * {@inheritdoc}
     * @throws LocalizedException
     */
    public function install(): void
    {
        $this->storeManager->setCurrentStore(Store::DISTRO_STORE_ID);
        $this->website->install(
            'Shopgate_WebsiteSampleData::fixtures/websites.csv',
            'Shopgate_WebsiteSampleData::fixtures/groups.csv',
            'Shopgate_WebsiteSampleData::fixtures/stores.csv',
            'Shopgate_WebsiteSampleData::fixtures/configs.csv'
        );
    }
}
